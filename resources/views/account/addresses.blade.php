@extends('account.layout')

@section('title', 'Sổ địa chỉ nhận hàng - Hương Mít')
@section('page_name', 'Sổ địa chỉ nhận hàng')

@section('account_content')
<div x-data="addressManager()" x-init="init()" class="space-y-6">

    <!-- Header & Add Address Button -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-800">Sổ địa chỉ giao hàng</h2>
            <p class="text-xs text-slate-400 mt-0.5">Lưu trước các địa chỉ nhận hàng giúp bạn đặt hàng nhanh chóng và thuận tiện hơn</p>
        </div>

        <button type="button" @click="openCreateModal()"
                class="px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/25 hover:shadow-lg transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Thêm địa chỉ mới</span>
        </button>
    </div>

    @if(session('address_success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('address_success') }}</span>
        </div>
    @endif

    <!-- Address Cards List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($addresses as $addr)
            <div class="bg-white rounded-3xl border {{ $addr->is_default ? 'border-primary shadow-md shadow-primary/10 ring-2 ring-primary/10' : 'border-slate-200/80 shadow-xs' }} p-6 flex flex-col justify-between transition-all hover:border-primary/60 group">
                
                <div>
                    <!-- Receiver Name & Badges -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl {{ $addr->is_default ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">{{ $addr->receiver_name }}</h3>
                                <div class="text-xs font-semibold text-slate-500 flex items-center gap-1.5 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>{{ $addr->receiver_phone }}</span>
                                </div>
                            </div>
                        </div>

                        @if($addr->is_default)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-primary text-white shadow-xs">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                Mặc định
                            </span>
                        @endif
                    </div>

                    <!-- Address details -->
                    <div class="space-y-1.5 text-xs text-slate-600 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-100 my-4">
                        <div class="font-bold text-slate-800">{{ $addr->detail_address }}</div>
                        <div class="text-slate-500 flex items-center gap-1 flex-wrap">
                            @if($addr->ward)
                                <span>{{ $addr->ward }},</span>
                            @endif
                            @if($addr->district)
                                <span>{{ $addr->district }},</span>
                            @endif
                            <span class="font-semibold text-slate-700">{{ $addr->province }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
                    <div>
                        @if(!$addr->is_default)
                            <form action="{{ route('account.addresses.default', $addr) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-slate-500 hover:text-primary font-semibold transition-colors underline-offset-2 hover:underline">
                                    Đặt làm mặc định
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Edit Button -->
                        <button type="button" 
                                @click="openEditModal({{ json_encode($addr) }})"
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 font-semibold transition-all flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Sửa</span>
                        </button>

                        <!-- Delete Button -->
                        <form action="{{ route('account.addresses.destroy', $addr) }}" method="POST"
                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này khỏi sổ địa chỉ không?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-400 flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl border border-slate-200/80 p-12 text-center text-slate-400">
                <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-primary-50 text-primary flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-700">Chưa có địa chỉ giao hàng nào</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Thêm địa chỉ giao hàng để chúng tôi giao sản phẩm tới tận tay bạn một cách chính xác và nhanh chóng nhất.
                </p>
                <button type="button" @click="openCreateModal()"
                        class="mt-5 px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm địa chỉ ngay</span>
                </button>
            </div>
        @endforelse
    </div>

    <!-- ── POPUP MODAL: THÊM / SỬA ĐỊA CHỈ ── -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="modalOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg border border-slate-100">

                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-primary-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white" x-text="isEditMode ? 'Chỉnh sửa địa chỉ giao hàng' : 'Thêm địa chỉ giao hàng mới'"></h3>
                            <p class="text-xs text-slate-300">Nhập thông tin người nhận và địa chỉ giao hàng chính xác</p>
                        </div>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Form -->
                <form :action="isEditMode ? ('/tai-khoan/dia-chi/' + form.id) : '{{ route('account.addresses.store') }}'" 
                      method="POST" 
                      class="p-6 space-y-4">
                    @csrf
                    <template x-if="isEditMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Row 1: Receiver Name & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Họ tên người nhận <span class="text-primary">*</span>
                            </label>
                            <input type="text" name="receiver_name" x-model="form.receiver_name" placeholder="vd: Nguyễn Văn A" required
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Số điện thoại <span class="text-primary">*</span>
                            </label>
                            <input type="tel" name="receiver_phone" x-model="form.receiver_phone" placeholder="vd: 0987654321" required
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>
                    </div>

                    <!-- Row 2: Province -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Tỉnh / Thành phố <span class="text-primary">*</span>
                        </label>
                        <select name="province" x-model="form.province" required
                                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                            <option value="">-- Chọn Tỉnh / Thành phố --</option>
                            <option value="Hà Nội">Hà Nội</option>
                            <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                            <option value="Đà Nẵng">Đà Nẵng</option>
                            <option value="Hải Phòng">Hải Phòng</option>
                            <option value="Cần Thơ">Cần Thơ</option>
                            <option value="Bình Dương">Bình Dương</option>
                            <option value="Đồng Nai">Đồng Nai</option>
                            <option value="Quảng Ninh">Quảng Ninh</option>
                            <option value="Bắc Ninh">Bắc Ninh</option>
                            <option value="Hải Dương">Hải Dương</option>
                            <option value="Hưng Yên">Hưng Yên</option>
                            <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                            <option value="Nam Định">Nam Định</option>
                            <option value="Thái Bình">Thái Bình</option>
                            <option value="Ninh Bình">Ninh Bình</option>
                            <option value="Thanh Hóa">Thanh Hóa</option>
                            <option value="Nghệ An">Nghệ An</option>
                            <option value="Hà Tĩnh">Hà Tĩnh</option>
                            <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                            <option value="Quảng Nam">Quảng Nam</option>
                            <option value="Quảng Ngãi">Quảng Ngãi</option>
                            <option value="Bình Định">Bình Định</option>
                            <option value="Khánh Hòa">Khánh Hòa</option>
                            <option value="Lâm Đồng">Lâm Đồng</option>
                            <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                            <option value="Tây Ninh">Tây Ninh</option>
                            <option value="Long An">Long An</option>
                            <option value="Tiền Giang">Tiền Giang</option>
                            <option value="Bến Tre">Bến Tre</option>
                            <option value="An Giang">An Giang</option>
                            <option value="Kiên Giang">Kiên Giang</option>
                            <option value="Khác">Tỉnh thành khác...</option>
                        </select>
                    </div>

                    <!-- Row 3: District & Ward -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Quận / Huyện
                            </label>
                            <input type="text" name="district" x-model="form.district" placeholder="vd: Quận Cầu Giấy, Huyện Thanh Trì..."
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Phường / Xã
                            </label>
                            <input type="text" name="ward" x-model="form.ward" placeholder="vd: Phường Dịch Vọng Hậu..."
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>
                    </div>

                    <!-- Row 4: Detail Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Địa chỉ cụ thể (Số nhà, ngõ, tên đường) <span class="text-primary">*</span>
                        </label>
                        <input type="text" name="detail_address" x-model="form.detail_address" placeholder="vd: Số 25 ngõ 180 đường Trần Duy Hưng" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>

                    <!-- Default Checkbox -->
                    <div class="pt-2 flex items-center gap-2.5">
                        <input type="checkbox" id="is_default" name="is_default" value="1" x-model="form.is_default"
                               class="rounded border-slate-300 text-primary focus:ring-primary/20">
                        <label for="is_default" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                            Đặt làm địa chỉ nhận hàng mặc định
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalOpen = false"
                                class="px-5 py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-all">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/25 hover:shadow-lg transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="isEditMode ? 'Cập nhật địa chỉ' : 'Lưu địa chỉ'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function addressManager() {
    return {
        modalOpen: false,
        isEditMode: false,
        form: {
            id: null,
            receiver_name: '',
            receiver_phone: '',
            province: '',
            district: '',
            ward: '',
            detail_address: '',
            is_default: false
        },

        init() {
            // Check if there are validation errors from previous submit to re-open modal
            @if($errors->any())
                this.modalOpen = true;
                this.form.receiver_name = '{{ old('receiver_name') }}';
                this.form.receiver_phone = '{{ old('receiver_phone') }}';
                this.form.province = '{{ old('province') }}';
                this.form.district = '{{ old('district') }}';
                this.form.ward = '{{ old('ward') }}';
                this.form.detail_address = '{{ old('detail_address') }}';
                this.form.is_default = {{ old('is_default') ? 'true' : 'false' }};
            @endif
        },

        openCreateModal() {
            this.isEditMode = false;
            this.form = {
                id: null,
                receiver_name: '{{ auth()->user()->name }}',
                receiver_phone: '{{ auth()->user()->phone ?? '' }}',
                province: '',
                district: '',
                ward: '',
                detail_address: '',
                is_default: {{ auth()->user()->addresses()->count() === 0 ? 'true' : 'false' }}
            };
            this.modalOpen = true;
        },

        openEditModal(addr) {
            this.isEditMode = true;
            this.form = {
                id: addr.id,
                receiver_name: addr.receiver_name || '',
                receiver_phone: addr.receiver_phone || '',
                province: addr.province || '',
                district: addr.district || '',
                ward: addr.ward || '',
                detail_address: addr.detail_address || '',
                is_default: Boolean(addr.is_default)
            };
            this.modalOpen = true;
        }
    };
}
</script>
@endpush
@endsection
