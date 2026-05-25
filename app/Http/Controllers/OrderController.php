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
            ->whereNotIn('order_status', [Order::ORDER_STATUS_RETURNED, Order::ORDER_STATUS_CANCELLED])
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
                'order_status' => Order::ORDER_STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'address' => $request->address,
            ]);

            $payment = $paymentGateway->createPayment($order, [
                'prefix' => 'SEWANA-ONLINE',
                'success_redirect_url' => route('penyewa.orders.show', $order->id),
                'failure_redirect_url' => route('penyewa.orders.show', $order->id),
            ]);

            $order->update([
                'payment_gateway' => $payment['gateway'],
                'payment_reference' => $payment['reference'],
                'payment_payload' => $payment['payload'],
            ]);

            return redirect($payment['payment_url'] ?: route('penyewa.orders.payment.instructions', $order->id))
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
    public function storeOffline(Request $request, PaymentGatewayService $paymentGateway)
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
            'payment_method' => ['required', Rule::in(Order::PAYMENT_METHODS)],
            'nominal_diterima' => 'required_if:payment_method,' . Order::PAYMENT_METHOD_CASH . '|nullable|numeric|min:0',
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

        $order = DB::transaction(function () use ($request, $paymentGateway) {
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
            $paymentMethod = $request->input('payment_method', Order::PAYMENT_METHOD_CASH);

            $amountReceived = null;
            $changeAmount = null;

            if ($paymentMethod === Order::PAYMENT_METHOD_CASH) {
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
                'order_status' => $paymentMethod === Order::PAYMENT_METHOD_CASH ? Order::ORDER_STATUS_RENTED : Order::ORDER_STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === Order::PAYMENT_METHOD_CASH ? Order::PAYMENT_STATUS_PAID : Order::PAYMENT_STATUS_PENDING,
                'payment_gateway' => null,
                'paid_at' => $paymentMethod === Order::PAYMENT_METHOD_CASH ? now() : null,
                'address' => $request->address,
            ]);
            if ($paymentMethod === Order::PAYMENT_METHOD_CASH) {
                $variant->decrement('stock', 1);
            }

            if ($paymentMethod === Order::PAYMENT_METHOD_QRIS) {
                $payment = $paymentGateway->createPayment($order, [
                    'prefix' => 'SEWANA-QRIS',
                    'payment_methods' => ['QRIS'],
                    'success_redirect_url' => route('pegawai.orders.offline-qris.show', $order),
                    'failure_redirect_url' => route('pegawai.orders.offline-qris.show', $order),
                    'description' => 'Sewana QRIS Offline #' . $order->id,
                ]);

                $order->update([
                    'payment_gateway' => $payment['gateway'],
                    'payment_reference' => $payment['reference'],
                    'payment_payload' => $payment['payload'],
                ]);
            }

            return $order;
        });
        if ($order->payment_method === Order::PAYMENT_METHOD_QRIS) {
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

        if ($order->source !== 'offline' || $order->payment_method !== Order::PAYMENT_METHOD_QRIS) {
            abort(404);
        }

        $order->load(['user', 'product', 'variant']);
        $paymentPayload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $paymentUrl = data_get($paymentPayload, 'payment_url')
            ?: data_get($paymentPayload, 'invoice_url')
            ?: data_get($paymentPayload, 'response.invoice_url');
        $qrString = data_get($paymentPayload, 'qr_data.qr_string')
            ?: data_get($paymentPayload, 'qr_data.qr_code')
            ?: data_get($paymentPayload, 'qr_data.qr_code_string');
        $qrImageUrl = data_get($paymentPayload, 'qr_data.qr_code_url')
            ?: data_get($paymentPayload, 'qr_data.qris_url');
        $paymentQrCodeSvg = $qrString
            ? $this->generateQrCodeSvg($qrString)
            : ($paymentUrl ? $this->generateQrCodeSvg($paymentUrl) : null);

        return view('orders.offline_qris', compact('order', 'paymentUrl', 'paymentQrCodeSvg', 'qrImageUrl'));
    }

    public function simulateOfflineQrisSuccess(Order $order)
    {
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403);
        }

        if (! app()->environment(['local', 'development', 'testing'])) {
            abort(404);
        }

        if ($order->source !== 'offline' || $order->payment_method !== Order::PAYMENT_METHOD_QRIS) {
            abort(404);
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return redirect()->route('pegawai.orders.offline-qris.show', $order)
                ->with('warning', 'Pembayaran pesanan ini sudah diterima.');
        }

        $order->update([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $order->payment_reference ?: sprintf('QRIS-FALLBACK-%s-%s', $order->id, now()->timestamp),
            'payment_payload' => [
                'provider' => 'local_fallback',
                'type' => 'offline_qris_fallback',
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

            if ($order->order_status !== Order::ORDER_STATUS_PENDING) {
                return back()->with('error', 'Pesanan sudah diproses.');
            }

            $requiresPaidBeforeApproval = $order->source === 'online'
                || ($order->source === 'offline' && $order->payment_method === Order::PAYMENT_METHOD_QRIS);

            if ($requiresPaidBeforeApproval && $order->payment_status !== Order::PAYMENT_STATUS_PAID) {
                $message = match ($order->payment_status) {
                    Order::PAYMENT_STATUS_PENDING => 'Menunggu pembayaran penyewa sebelum pesanan dapat disetujui.',
                    Order::PAYMENT_STATUS_FAILED => 'Pembayaran penyewa gagal. Pesanan tidak dapat disetujui.',
                    Order::PAYMENT_STATUS_EXPIRED => 'Pembayaran penyewa kedaluwarsa. Pesanan tidak dapat disetujui.',
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
                'order_status' => Order::ORDER_STATUS_APPROVED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
            ]);

            return back()->with('success', 'Pesanan disetujui dan stok telah dikurangi.');
        });
    }

    /** Mark the item as handed over by staff. */
    public function handover(Request $request, $id)
    {
        $request->validate([
            'payment_status' => ['nullable', Rule::in([Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PENDING])],
        ]);

        $response = DB::transaction(function () use ($request, $id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== Order::ORDER_STATUS_APPROVED) {
                return back()->with('error', 'Pesanan harus berstatus disetujui sebelum diserahkan. Stok tidak diubah.');
            }

            $payment = $request->input('payment_status', Order::PAYMENT_STATUS_PENDING);

            // Do not decrement stock here because it was already decremented during approval.
            $order->update([
                'order_status' => Order::ORDER_STATUS_RENTED,
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

            if ($order->order_status !== Order::ORDER_STATUS_RENTED) {
                return back()->with('error', 'Hanya pesanan yang sedang disewa yang bisa dikembalikan. Stok tidak diubah.');
            }

            if ($order->variant_id) {
                // LOCK VARIANT TO PREVENT RACE CONDITION
                $variant = ProductVariant::lockForUpdate()
                    ->findOrFail($order->variant_id);
                $variant->increment('stock', 1);
            }

            $order->update(['order_status' => Order::ORDER_STATUS_RETURNED]);

            return back()->with('success', 'Barang berhasil dikembalikan. Stok telah bertambah.');
        });
    }

    /** Cancel a pending staff order. */
    public function cancel($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== Order::ORDER_STATUS_PENDING) {
                return back()->with('error', 'Pesanan sudah diproses dan tidak bisa dibatalkan.');
            }

            $order->update(['order_status' => Order::ORDER_STATUS_CANCELLED]);

            return back()->with('success', 'Pesanan berhasil dibatalkan.');
        });
    }

    /** Reject a pending online order. */
    public function reject($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);

            if ($order->order_status !== Order::ORDER_STATUS_PENDING) {
                return back()->with('error', 'Pesanan sudah diproses dan tidak bisa ditolak.');
            }

            $order->update(['order_status' => Order::ORDER_STATUS_CANCELLED]);

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

    public function status($id)
    {
        $order = Order::query()->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasAnyRole(['pegawai', 'pemilik']) && (int) $order->user_id !== (int) Auth::id()) {
            abort(403, 'Tidak berwenang melihat status pesanan ini.');
        }

        return response()->json([
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'updated_at' => $order->updated_at?->toISOString(),
        ]);
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
            'payment_status' => ['required', Rule::in([Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PENDING])],
        ]);

        // ADD AUTHORIZATION CHECK
        if (! Auth::user()->hasAnyRole(['pegawai', 'pemilik'])) {
            abort(403, 'Tidak berwenang memperbarui status pembayaran');
        }

        $order = Order::findOrFail($id);

        if (! in_array($order->order_status, [Order::ORDER_STATUS_APPROVED, Order::ORDER_STATUS_RENTED])) {
            return back()->with('error', 'Status pesanan tidak valid untuk memperbarui pembayaran.');
        }

        // VALIDATE TRANSITION
        $allowedTransitions = [
            Order::PAYMENT_STATUS_PENDING => [Order::PAYMENT_STATUS_PAID],
            Order::PAYMENT_STATUS_PAID => [Order::PAYMENT_STATUS_PENDING],
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

        if (! in_array($order->order_status, [Order::ORDER_STATUS_PENDING, Order::ORDER_STATUS_CANCELLED])) {
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
        $totalRevenue = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('total_price');
        $activeRentals = $orders->where('order_status', Order::ORDER_STATUS_RENTED)->count();
        $returnedOrders = $orders->where('order_status', Order::ORDER_STATUS_RETURNED)->count();

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
