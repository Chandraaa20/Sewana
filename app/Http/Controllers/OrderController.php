<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PaymentGatewayService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /** Show the customer's own order list. */
    public function index()
    {
        $orders = Order::with(['product.images', 'variant'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /** Manage orders for staff. */
    public function staffIndex(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $orders = Order::with(['user', 'product.images', 'variant'])
            ->whereNotIn('order_status', ['returned', 'cancelled'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn($pq) => $pq
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"))
                        ->when(ctype_digit($search), fn($idQuery) => $idQuery->orWhere('id', (int) $search))
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('orders.staff_index', compact('orders'));
    }

    /** Show all rentals for staff. */
    public function aOrders(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $orders = Order::with(['user', 'product.images', 'variant'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn($pq) => $pq
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"))
                        ->when(ctype_digit($search), fn($idQuery) => $idQuery->orWhere('id', (int) $search))
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('order_status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('orders.staff_allorders', compact('orders'));
    }

    /** Show the product rental form for customers. */
    public function create(Request $request)
    {
        if (! $request->has('product_id')) {
            return redirect()->route('penyewa.products.index')
                ->with('error', 'Produk tidak ditemukan.');
        }

        $product = Product::with(['variants', 'images'])
            ->where('status', 'active')
            ->whereHas('variants', function ($query) {
                $query->where('stock', '>', 0)
                    ->where('status', 'tersedia');
            })
            ->findOrFail($request->product_id);

        return view('orders.create', compact('product'));
    }

    /** Store an online customer order. */
    public function store(Request $request, PaymentGatewayService $paymentGateway)
    {
        $today = now()->toDateString();

        $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn($query) => $query->where('status', 'active')),
            ],
            'variant_id' => [
                'required',
                Rule::exists('product_variants', 'id')->where(function ($query) use ($request) {
                    $query->where('product_id', $request->input('product_id'))
                        ->where('stock', '>', 0)
                        ->where('status', 'tersedia');
                }),
            ],
            'customer_name' => 'required|string|max:255',
            'identity_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today],
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'address' => 'required|string|max:255',
        ], [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid atau sedang tidak aktif.',
            'variant_id.required' => 'Varian wajib dipilih.',
            'variant_id.exists' => 'Varian tidak valid, tidak sesuai produk, stoknya habis, atau statusnya tidak tersedia.',
            'customer_name.required' => 'Nama penyewa wajib diisi.',
            'identity_photo.required' => 'Foto identitas wajib diunggah.',
            'identity_photo.image' => 'Foto identitas harus berupa gambar.',
            'identity_photo.mimes' => 'Foto identitas harus berformat JPG, JPEG, PNG, atau WEBP.',
            'identity_photo.max' => 'Ukuran foto identitas maksimal 10 MB.',
            'start_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'start_date.date_format' => 'Format tanggal mulai sewa tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh sebelum hari ini.',
            'end_date.required' => 'Tanggal selesai sewa wajib diisi.',
            'end_date.date_format' => 'Format tanggal selesai sewa tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai sewa tidak boleh sebelum tanggal mulai sewa.',
            'address.required' => 'Alamat pengiriman atau penjemputan wajib diisi.',
        ]);

        return DB::transaction(function () use ($request, $paymentGateway) {
            $variant = ProductVariant::with('product')
                ->whereKey($request->variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $variant->product_id !== (int) $request->product_id) {
                return back()->with('error', 'Varian tidak sesuai dengan produk yang dipilih.')->withInput();
            }

            if ($variant->stock <= 0) {
                return back()->with('error', 'Mohon maaf, stok varian ini baru saja habis.')->withInput();
            }

            if ($variant->status !== 'tersedia') {
                return back()->with('error', 'Varian tidak dapat disewa karena statusnya tidak tersedia.')->withInput();
            }

            if (! $variant->product || $variant->product->status !== 'active') {
                return back()->with('error', 'Produk tidak dapat disewa karena sedang tidak aktif.')->withInput();
            }

            $start = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $request->end_date)->startOfDay();
            $rentDays = $start->diffInDays($end) + 1;

            $totalPrice = $variant->price * $rentDays;
            $photoPath = $request->file('identity_photo')->store('identity_photos', 'public');

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'identity_photo' => $photoPath,
                'source' => 'online',
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'rent_days' => $rentDays,
                'price_per_day' => $variant->price,
                'total_price' => $totalPrice,
                'order_status' => 'pending',
                'payment_status' => 'pending',
                'address' => $request->address,
            ]);

            $payment = $paymentGateway->createPayment($order);

            $order->update([
                'payment_gateway' => $payment['gateway'],
                'payment_reference' => $payment['reference'],
            ]);

            return redirect($payment['payment_url'])
                ->with('success', 'Pesanan berhasil dibuat. Silakan ikuti instruksi pembayaran.');
        });
    }

    /** Show the offline order creation form for staff. */
    public function createOffline()
    {
        $products = Product::with(['variants', 'images'])
            ->where('status', 'active')
            ->whereHas('variants', function ($query) {
                $query->where('stock', '>', 0)
                    ->where('status', 'tersedia');
            })
            ->get();

        return view('orders.create_offline', compact('products'));
    }

    /** Store an offline staff order. */
    public function storeOffline(Request $request)
    {
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403, 'Hanya staf dan pemilik yang bisa membuat pesanan offline.');
        }

        $today = now()->toDateString();

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'identity_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn($query) => $query->where('status', 'active')),
            ],
            'variant_id' => [
                'required',
                Rule::exists('product_variants', 'id')->where(function ($query) use ($request) {
                    $query->where('product_id', $request->input('product_id'))
                        ->where('stock', '>', 0)
                        ->where('status', 'tersedia');
                }),
            ],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today],
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'address' => 'required|string|max:255',
            'payment_method' => 'required|in:cash,qris',
            'nominal_diterima' => 'required_if:payment_method,cash|nullable|numeric|min:0',
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'identity_photo.required' => 'Foto identitas wajib diunggah.',
            'identity_photo.image' => 'Foto identitas harus berupa gambar.',
            'identity_photo.mimes' => 'Foto identitas harus berformat JPG, JPEG, PNG, atau WEBP.',
            'identity_photo.max' => 'Ukuran foto identitas maksimal 10 MB.',
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid atau sedang tidak aktif.',
            'variant_id.required' => 'Varian wajib dipilih.',
            'variant_id.exists' => 'Varian tidak valid, tidak sesuai produk, stoknya habis, atau statusnya tidak tersedia.',
            'start_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'start_date.date_format' => 'Format tanggal mulai sewa tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh sebelum hari ini.',
            'end_date.required' => 'Tanggal selesai sewa wajib diisi.',
            'end_date.date_format' => 'Format tanggal selesai sewa tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai sewa tidak boleh sebelum tanggal mulai sewa.',
            'address.required' => 'Alamat wajib diisi.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'nominal_diterima.required_if' => 'Nominal diterima wajib diisi untuk pembayaran tunai.',
            'nominal_diterima.numeric' => 'Nominal diterima harus berupa angka.',
            'nominal_diterima.min' => 'Nominal diterima tidak boleh kurang dari 0.',
        ]);

        $order = DB::transaction(function () use ($request) {
            $variant = ProductVariant::with('product')
                ->whereKey($request->variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $variant->product_id !== (int) $request->product_id) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Varian tidak sesuai dengan produk yang dipilih.',
                ]);
            }

            if ($variant->stock <= 0) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Mohon maaf, stok varian ini baru saja habis.',
                ]);
            }

            if ($variant->status !== 'tersedia') {
                throw ValidationException::withMessages([
                    'variant_id' => 'Varian tidak dapat disewa karena statusnya tidak tersedia.',
                ]);
            }

            if (! $variant->product || $variant->product->status !== 'active') {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk tidak dapat disewa karena sedang tidak aktif.',
                ]);
            }

            $start = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $request->end_date)->startOfDay();
            $rentDays = $start->diffInDays($end) + 1;

            $totalPrice = $variant->price * $rentDays;
            $totalAmount = (int) round($totalPrice);
            $paymentMethod = $request->input('payment_method', 'cash');

            $amountReceived = null;
            $changeAmount = null;

            if ($paymentMethod === 'cash') {
                $amountReceived = (int) round((float) $request->nominal_diterima);

                if ($amountReceived < $totalAmount) {
                    throw ValidationException::withMessages([
                        'nominal_diterima' => 'Nominal diterima tidak boleh kurang dari total pembayaran.',
                    ]);
                }

                $changeAmount = $amountReceived - $totalAmount;
            }

            $photoPath = $request->file('identity_photo')->store('identity_photos', 'public');

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'identity_photo' => $photoPath,
                'source' => 'offline',
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'rent_days' => $rentDays,
                'price_per_day' => $variant->price,
                'total_price' => $totalPrice,
                'amount_received' => $amountReceived,
                'change_amount' => $changeAmount,
                'order_status' => $paymentMethod === 'cash' ? 'rented' : 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'cash' ? 'paid' : 'pending',
                'payment_gateway' => $paymentMethod === 'qris' ? 'dummy' : null,
                'paid_at' => $paymentMethod === 'cash' ? now() : null,
                'address' => $request->address,
            ]);
            if ($paymentMethod === 'cash') {
                $variant->decrement('stock', 1);
            }

            if ($paymentMethod === 'qris') {
                $order->update([
                    'payment_reference' => sprintf('QRIS-DUMMY-%s-%s', $order->id, now()->timestamp),
                ]);
            }

            return $order;
        });
        if ($order->payment_method === 'qris') {
            return redirect()->route('pegawai.orders.offline-qris.show', $order)
                ->with('success', 'Pesanan offline QRIS berhasil dibuat dan menunggu pembayaran.');
        }
        return redirect()->route('pegawai.orders.index')
            ->with('success', 'Pesanan offline tunai berhasil ditambahkan.');
    }
    public function offlineQrisShow(Order $order)
    {
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403);
        }

        if ($order->source !== 'offline' || $order->payment_method !== 'qris') {
            abort(404);
        }

        $paymentUrl = route('pegawai.orders.offline-qris.show', $order);
        $paymentQrCodeSvg = $this->generateQrCodeSvg($paymentUrl);

        $order->load(['user', 'product', 'variant']);

        return view('orders.offline_qris_dummy', compact('order', 'paymentUrl', 'paymentQrCodeSvg'));
    }

    public function simulateOfflineQrisSuccess(Order $order)
    {
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403);
        }

        if (! app()->environment(['local', 'development', 'testing'])) {
            abort(404);
        }

        if ($order->source !== 'offline' || $order->payment_method !== 'qris') {
            abort(404);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('pegawai.orders.offline-qris.show', $order)
                ->with('warning', 'Pembayaran pesanan ini sudah diterima.');
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $order->payment_reference ?: sprintf('QRIS-DUMMY-%s-%s', $order->id, now()->timestamp),
            'payment_payload' => [
                'provider' => 'dummy',
                'type' => 'offline_qris',
                'status' => 'success',
                'simulated_at' => now()->toDateTimeString(),
                'handled_by' => Auth::id(),
            ],
        ]);

        return redirect()->route('pegawai.orders.offline-qris.show', $order)
            ->with('success', 'Pembayaran QRIS berhasil dikonfirmasi oleh sistem.');
    }
    /** Approve an order and decrement stock. */
    public function approve(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== 'pending') {
                return back()->with('error', 'Pesanan sudah diproses.');
            }

            $requiresPaidBeforeApproval = $order->source === 'online'
                || ($order->source === 'offline' && $order->payment_method === 'qris');

            if ($requiresPaidBeforeApproval && $order->payment_status !== 'paid') {
                $message = match ($order->payment_status) {
                    'pending' => 'Menunggu pembayaran penyewa sebelum pesanan dapat disetujui.',
                    'failed' => 'Pembayaran penyewa gagal. Pesanan tidak dapat disetujui.',
                    'expired' => 'Pembayaran penyewa kedaluwarsa. Pesanan tidak dapat disetujui.',
                    default => 'Status pembayaran belum valid untuk menyetujui pesanan.',
                };

                return back()->with('error', $message);
            }

            if (! $order->variant_id) {
                return back()->with('error', 'Pesanan varian tidak valid.');
            }

            $variant = ProductVariant::with('product')
                ->whereKey($order->variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure stock is still available when staff approves the order.
            if ($variant->stock <= 0) {
                return back()->with('error', 'Gagal menyetujui: stok barang saat ini sudah habis.');
            }

            if ($variant->status !== 'tersedia') {
                return back()->with('error', 'Gagal menyetujui: varian tidak tersedia untuk disewa.');
            }

            if (! $variant->product || $variant->product->status !== 'active') {
                return back()->with('error', 'Gagal menyetujui: produk sedang tidak aktif.');
            }

            // Decrement stock because the item has been reserved or paid.
            $variant->decrement('stock', 1);

            $order->update([
                'order_status' => 'approved',
                'payment_status' => 'paid',
            ]);

            return back()->with('success', 'Pesanan disetujui dan stok telah dikurangi.');
        });
    }

    /** Mark the item as handed over by staff. */
    public function handover(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'nullable|in:paid,pending',
        ]);

        $response = DB::transaction(function () use ($request, $id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== 'approved') {
                return back()->with('error', 'Pesanan harus berstatus disetujui sebelum diserahkan. Stok tidak diubah.');
            }

            $payment = $request->input('payment_status', 'pending');

            // Do not decrement stock here because it was already decremented during approval.
            $order->update([
                'order_status' => 'rented',
                'payment_status' => $payment,
            ]);
        });

        if ($response) {
            return $response;
        }

        return back()->with('success', 'Barang diserahkan. Status pesanan menjadi sedang disewa.');
    }

    /** Mark the item as returned, restore stock, and update status. */
    public function returned($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== 'rented') {
                return back()->with('error', 'Hanya pesanan yang sedang disewa yang bisa dikembalikan. Stok tidak diubah.');
            }

            if ($order->variant_id) {
                // LOCK VARIANT TO PREVENT RACE CONDITION
                $variant = ProductVariant::lockForUpdate()
                    ->findOrFail($order->variant_id);
                $variant->increment('stock', 1);
            }

            $order->update(['order_status' => 'returned']);

            return back()->with('success', 'Barang berhasil dikembalikan. Stok telah bertambah.');
        });
    }

    /** Cancel a pending staff order. */
    public function cancel($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== 'pending') {
                return back()->with('error', 'Pesanan sudah diproses dan tidak bisa dibatalkan.');
            }

            $order->update(['order_status' => 'cancelled']);

            return back()->with('success', 'Pesanan berhasil dibatalkan.');
        });
    }

    /** Reject a pending online order. */
    public function reject($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== 'pending') {
                return back()->with('error', 'Pesanan sudah diproses dan tidak bisa ditolak.');
            }

            $order->update(['order_status' => 'cancelled']);

            return back()->with('success', 'Pesanan berhasil ditolak dan dibatalkan.');
        });
    }

    /** Show order details. */
    public function show($id)
    {
        $query = Order::with(['user', 'product.images', 'variant']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check whether the user has the staff or owner role.
        if ($user->hasAnyRole(['pegawai', 'pemilik'])) {
        } else {
            $query->where('user_id', Auth::id());
        }

        $order = $query->findOrFail($id);
        $verificationUrl = null;
        $verificationQrCodeSvg = null;

        if (filled($order->validation_token)) {
            $verificationUrl = route('transactions.verify', $order->validation_token);
            $verificationQrCodeSvg = $this->generateQrCodeSvg($verificationUrl);
        }

        return view('orders.show', compact('order', 'verificationUrl', 'verificationQrCodeSvg'));
    }

    private function generateQrCodeSvg(string $content): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(180),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($content);
    }

    /** Update payment status. */
    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:paid,pending',
        ]);

        // ADD AUTHORIZATION CHECK
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403, 'Tidak berwenang memperbarui status pembayaran');
        }

        $order = Order::findOrFail($id);

        if (! in_array($order->order_status, ['approved', 'rented'])) {
            return back()->with('error', 'Status pesanan tidak valid untuk memperbarui pembayaran.');
        }

        // VALIDATE TRANSITION
        $allowedTransitions = [
            'pending' => ['paid'],
            'paid' => ['pending'],
        ];

        $currentStatus = $order->payment_status;
        $newStatus = $request->payment_status;

        if ($currentStatus === $newStatus) {
            return back()->with('warning', 'Status pembayaran sudah sama.');
        }

        if (
            ! isset($allowedTransitions[$currentStatus]) ||
            ! in_array($newStatus, $allowedTransitions[$currentStatus])
        ) {
            return back()->with(
                'error',
                "Transisi dari {$currentStatus} ke {$newStatus} tidak diizinkan."
            );
        }

        $order->update([
            'payment_status' => $newStatus,
        ]);

        return back()->with('success', 'Status pembayaran diperbarui.');
    }

    /** Delete a customer order. */
    public function destroy($id)
    {
        // Prevent IDOR by ensuring customers can only delete their own orders.
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if (! in_array($order->order_status, ['pending', 'cancelled'])) {
            return back()->with('error', 'Pesanan tidak bisa dihapus karena sudah diproses oleh staf.');
        }
        if ($order->identity_photo) {
            Storage::disk('public')->delete($order->identity_photo);
        }

        if ($order->bukti_pembayaran) {
            Storage::disk('public')->delete($order->bukti_pembayaran);
        }
        $order->delete();

        return redirect()->route('penyewa.orders.index')
            ->with('success', 'Pesanan berhasil dihapus.');
    }

    /** Show reports for staff and admin users. */
    public function report(Request $request)
    {
        // Use full-day bounds so 23:59:59 remains included in the date range.
        $start = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $end = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $orders = Order::with(['product', 'variant', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->where('payment_status', 'paid')->sum('total_price');
        $activeRentals = $orders->where('order_status', 'rented')->count();
        $returnedOrders = $orders->where('order_status', 'returned')->count();

        // Most frequently rented products.
        $topProducts = Order::select('product_id', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->with('product')
            ->take(5)
            ->get();

        // Monthly transaction chart for the selected date range.
        $monthlyOrders = Order::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as total')
        )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('reports.index', compact(
            'totalOrders',
            'totalRevenue',
            'activeRentals',
            'returnedOrders',
            'topProducts',
            'monthlyOrders',
            'start',
            'end'
        ));
    }
}
