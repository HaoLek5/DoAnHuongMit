@extends('account.layout')

@section('title', 'Sổ địa chỉ nhận hàng - Hương Mít')
@section('page_name', 'Sổ địa chỉ nhận hàng')

@section('account_content')
<div x-data="addressManager()" x-init="init()" class="space-y-6">

    <!-- Header & Add Address Button -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-800">Sổ địa chỉ nhận hàng</h2>
        </div>

        <button type="button" @click="openCreateModal()"
                class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
            <span>+ Thêm địa chỉ mới</span>
        </button>
    </div>

    @if(session('address_success'))
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('address_success') }}</span>
        </div>
    @endif

    <!-- Address Cards List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        @forelse($addresses as $addr)
            <div class="bg-white rounded-3xl border {{ $addr->is_default ? 'border-primary/80 ring-1 ring-primary/20 shadow-xs' : 'border-slate-200/80 shadow-xs' }} p-5 sm:p-6 flex flex-col justify-between transition-all hover:border-primary/50">
                
                <div>
                    <!-- Receiver Name, Phone & Default Badge -->
                    <div class="flex items-center justify-between gap-3 mb-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-slate-800">{{ $addr->receiver_name }}</span>
                            <span class="text-xs text-slate-300">|</span>
                            <span class="text-xs font-semibold text-slate-600">{{ $addr->receiver_phone }}</span>
                        </div>

                        @if($addr->is_default)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary text-white">
                                Mặc định
                            </span>
                        @endif
                    </div>

                    <!-- Address details -->
                    <div class="text-xs text-slate-600 space-y-0.5 my-3">
                        <div class="font-semibold text-slate-800">{{ $addr->detail_address }}</div>
                        <div class="text-slate-500">
                            {{ implode(', ', array_filter([$addr->ward, $addr->district, $addr->province])) }}
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3 text-xs">
                    <div>
                        @if(!$addr->is_default)
                            <form action="{{ route('account.addresses.default', $addr) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-slate-500 hover:text-primary font-semibold transition-colors">
                                    Đặt làm mặc định
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Edit Button -->
                        <button type="button" 
                                @click="openEditModal({{ json_encode($addr) }})"
                                class="px-3 py-1.5 rounded-xl border border-slate-200 hover:border-primary hover:text-primary text-slate-600 font-semibold transition-all">
                            Sửa
                        </button>

                        <!-- Delete Button -->
                        <form action="{{ route('account.addresses.destroy', $addr) }}" method="POST"
                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này khỏi sổ địa chỉ không?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="px-3 py-1.5 rounded-xl border border-slate-200 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600 text-slate-400 font-semibold transition-all">
                                Xóa
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-12 text-center text-slate-400">
                <h3 class="text-sm font-bold text-slate-700">Chưa có địa chỉ giao hàng nào</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Thêm địa chỉ giao hàng để chúng tôi giao sản phẩm tới tận tay bạn một cách nhanh chóng nhất.
                </p>
                <button type="button" @click="openCreateModal()"
                        class="mt-4 px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-md shadow-primary/20 transition-all inline-flex items-center gap-1.5">
                    <span>+ Thêm địa chỉ ngay</span>
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
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg border border-primary-100/80">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 id="modal-title" class="font-bold text-base text-slate-800" x-text="isEditMode ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ mới'"></h3>
                    <button type="button" @click="modalOpen = false"
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Họ tên người nhận <span class="text-primary">*</span>
                            </label>
                            <input type="text" name="receiver_name" x-model="form.receiver_name" placeholder="vd: Nguyễn Văn A" required
                                   class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Số điện thoại <span class="text-primary">*</span>
                            </label>
                            <input type="tel" name="receiver_phone" x-model="form.receiver_phone" placeholder="vd: 0987654321" required
                                   class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Row 2: Province -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tỉnh / Thành phố <span class="text-primary">*</span>
                        </label>
                        <select name="province" x-model="form.province" required
                                class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none bg-white transition-all">
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
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Quận / Huyện
                            </label>
                            <input type="text" name="district" x-model="form.district" placeholder="vd: Quận Cầu Giấy, Huyện Thanh Trì..."
                                   class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Phường / Xã
                            </label>
                            <input type="text" name="ward" x-model="form.ward" placeholder="vd: Phường Dịch Vọng Hậu..."
                                   class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Row 4: Detail Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Địa chỉ cụ thể (Số nhà, tên đường) <span class="text-primary">*</span>
                        </label>
                        <input type="text" name="detail_address" x-model="form.detail_address" placeholder="vd: Số 25 ngõ 180 đường Trần Duy Hưng" required
                               class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 text-xs font-semibold focus:border-primary outline-none transition-all">
                    </div>

                    <!-- Default Checkbox -->
                    <div class="pt-1 flex items-center gap-2">
                        <input type="checkbox" id="is_default" name="is_default" value="1" x-model="form.is_default"
                               class="rounded border-slate-300 text-primary focus:ring-primary/20 cursor-pointer">
                        <label for="is_default" class="text-xs text-slate-600 cursor-pointer select-none">
                            Đặt làm địa chỉ nhận hàng mặc định
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalOpen = false"
                                class="px-5 py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition-all">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all">
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
