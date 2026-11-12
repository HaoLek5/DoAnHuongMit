{{-- ── HƯƠNG MÍT PRODUCT RECOMMENDATION CHATBOT ── --}}
<div x-data="haomilkChatbot()" x-init="init()" class="relative z-50">

    {{-- ── FLOATING TRIGGER BUTTON & SPEECH BUBBLE ── --}}
    <div class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 flex items-end gap-2.5"
         x-show="!isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100">

        <!-- Dismissible speech bubble teaser -->
        <div x-show="showTeaser" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             @click="toggleChat()"
             class="hidden md:flex items-center gap-2.5 bg-white border border-primary-200/90 text-slate-700 px-4 py-2.5 rounded-2xl shadow-lg cursor-pointer hover:border-primary transition-all group">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <div class="text-left">
                <p class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors">Cần tư vấn chọn sản phẩm cho bé?</p>
                <p class="text-[11px] text-slate-500">Hỏi trợ lý ảo Hương Mít ngay!</p>
            </div>
            <button type="button" @click.stop="showTeaser = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors" title="Đóng gợi ý">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Main Circular Trigger Button -->
        <button type="button" 
                @click="toggleChat()"
                class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-tr from-primary to-rose-400 text-white shadow-xl shadow-primary/30 hover:shadow-2xl hover:scale-105 active:scale-95 transition-all flex items-center justify-center group focus:outline-none focus:ring-4 focus:ring-primary/20"
                aria-label="Mở trợ lý tư vấn sản phẩm">
            
            <!-- Animated Ping Badge -->
            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
            </span>

            <!-- Cute Assistant / Milk Icon -->
            <svg class="w-7 h-7 group-hover:rotate-6 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </button>
    </div>

    {{-- ── EXPANDED CHAT WINDOW ── --}}
    <div x-show="isOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-4 right-3 sm:bottom-6 sm:right-6 z-50 w-[94vw] sm:w-[420px] h-[580px] max-h-[88vh] bg-white rounded-3xl shadow-2xl border border-primary-100 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-primary via-primary-600 to-rose-500 text-white flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="relative w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white border border-white/20 shadow-xs">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-primary"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white flex items-center gap-1.5 font-display">
                        <span>Trợ lý tư vấn Hương Mít</span>
                        <span class="px-1.5 py-0.5 rounded-md bg-white/20 text-[10px] font-sans font-semibold">AI Bot</span>
                    </h3>
                    <p class="text-[11px] text-white/80">Chuyên tư vấn sản phẩm Mẹ & Bé</p>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-1">
                <button type="button" @click="resetChat()" 
                        class="p-2 text-white/80 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        title="Làm mới cuộc trò chuyện">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button" @click="toggleChat()" 
                        class="p-2 text-white/80 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        title="Thu nhỏ">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Scope Notice Banner -->
        <div class="px-4 py-2 bg-primary-50/70 border-b border-primary-100/70 flex items-center gap-2 text-[11px] text-primary-800">
            <svg class="w-3.5 h-3.5 text-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="truncate">Trợ lý chỉ hỗ trợ gợi ý & tư vấn sản phẩm tại cửa hàng</span>
        </div>

        <!-- Chat Body / Messages Scroll Area -->
        <div x-ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-milk/40 scroll-smooth">
            
            <template x-for="(msg, idx) in messages" :key="idx">
                <div class="space-y-2">
                    
                    <!-- Message Bubble Row -->
                    <div class="flex items-start gap-2.5" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                        
                        <!-- Bot Avatar on left -->
                        <template x-if="msg.sender === 'bot'">
                            <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-primary to-rose-400 text-white flex items-center justify-center flex-shrink-0 text-xs shadow-xs mt-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </template>

                        <!-- Bubble Content -->
                        <div class="max-w-[85%] rounded-2xl p-3.5 text-xs leading-relaxed shadow-xs"
                             :class="msg.sender === 'user' 
                                 ? 'bg-primary text-white rounded-tr-xs' 
                                 : (msg.is_out_of_scope ? 'bg-amber-50 border border-amber-200 text-amber-900 rounded-tl-xs' : 'bg-white border border-slate-200/80 text-slate-800 rounded-tl-xs')">
                            
                            <!-- Notice Badge for Out-of-Scope queries -->
                            <template x-if="msg.is_out_of_scope">
                                <div class="flex items-center gap-1.5 text-amber-700 font-bold mb-1 pb-1 border-b border-amber-200/70 text-[11px]">
                                    <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>Yêu cầu ngoài phạm vi sản phẩm</span>
                                </div>
                            </template>

                            <div class="whitespace-pre-line font-medium leading-relaxed" x-html="renderFormattedText(msg.text, msg.sender)"></div>
                            
                            <span class="block text-[10px] mt-1 text-right"
                                  :class="msg.sender === 'user' ? 'text-white/70' : 'text-slate-400'"
                                  x-text="msg.time"></span>
                        </div>
                    </div>

                    <!-- Recommended Product Cards (When available) -->
                    <template x-if="msg.products && msg.products.length > 0">
                        <div class="pl-9 space-y-2 pt-1">
                            <p class="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                                <span>Gợi ý sản phẩm phù hợp:</span>
                                <span class="text-primary font-mono" x-text="'(' + msg.products.length + ')'"></span>
                            </p>

                            <div class="grid grid-cols-1 gap-2.5">
                                <template x-for="p in msg.products" :key="p.id">
                                    <div class="bg-white rounded-2xl border border-slate-200 p-2.5 shadow-xs hover:border-primary transition-all flex items-center gap-3 group">
                                        
                                        <!-- Product Image -->
                                        <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center p-1">
                                            <img :src="p.image_url" :alt="p.name" class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition-transform duration-300">
                                        </div>

                                        <!-- Product Info -->
                                        <div class="flex-1 min-w-0 text-xs">
                                            <span class="text-[10px] font-bold text-primary block truncate" x-text="p.category_name"></span>
                                            <h4 class="font-bold text-slate-800 line-clamp-1 group-hover:text-primary transition-colors text-xs" :title="p.name" x-text="p.name"></h4>
                                            
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="font-extrabold text-primary text-xs" x-text="p.formatted_price"></span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 truncate" x-text="p.variants_summary"></span>
                                            </div>

                                            <div class="flex items-center justify-between mt-1.5 pt-1 border-t border-slate-100">
                                                <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Còn <span x-text="p.total_stock"></span> sp</span>
                                                </span>

                                                <button type="button" @click="viewProduct(p)"
                                                        class="px-2.5 py-1 rounded-lg bg-primary hover:bg-primary-600 text-white font-bold text-[10px] shadow-xs transition-colors flex items-center gap-1">
                                                    <span>Xem chi tiết</span>
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </template>

            <!-- Loading Indicator -->
            <div x-show="isLoading" class="flex items-center gap-2 pl-9">
                <div class="bg-white border border-slate-200 px-3.5 py-2.5 rounded-2xl rounded-tl-xs shadow-xs flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-primary animate-bounce"></span>
                    <span class="w-2 h-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-2 h-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.3s"></span>
                    <span class="text-[11px] ml-1 font-medium">Trợ lý đang tìm sản phẩm...</span>
                </div>
            </div>

        </div>

        <!-- Chat Footer & Input Form -->
        <div class="p-3 bg-white border-t border-slate-100 space-y-2">
            <form @submit.prevent="submitForm()" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="text" 
                           x-ref="messageInput"
                           x-model="inputMessage" 
                           :disabled="isLoading"
                           placeholder="Nhập yêu cầu (độ tuổi, cân nặng, tình trạng bé)..."
                           maxlength="300"
                           class="w-full pl-3.5 pr-8 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none disabled:bg-slate-50">
                    
                    <span class="absolute right-2.5 top-3 text-[10px] text-slate-300 pointer-events-none" 
                          x-text="inputMessage.length ? (inputMessage.length + '/300') : ''"></span>
                </div>

                <button type="submit" 
                        :disabled="!inputMessage.trim() || isLoading"
                        class="w-10 h-10 rounded-2xl bg-primary hover:bg-primary-600 disabled:opacity-40 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-primary/25 transition-all active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19V5m-7 7l7-7 7 7"/>
                    </svg>
                </button>
            </form>

            <div class="text-center">
                <span class="text-[10px] text-slate-400 italic">
                    💡 Chỉ tư vấn sản phẩm mẹ & bé tại cửa hàng Hương Mít
                </span>
            </div>
        </div>

    </div>

</div>

<script>
function haomilkChatbot() {
    return {
        isOpen: false,
        showTeaser: true,
        isLoading: false,
        inputMessage: '',
        messages: [],

        init() {
            // Initial greeting message
            this.messages = [
                {
                    sender: 'bot',
                    text: "Dạ Hương Mít xin chào bạn! 🍼✨\n\nEm là trợ lý ảo chuyên tư vấn sản phẩm cho Mẹ & Bé. Bạn cần tìm sữa tăng cân, sữa mát cho bé mấy tuổi, hay đang tìm tã bỉm, bình sữa? Hãy nhắn cho em để nhận gợi ý chính xác nhé!",
                    products: [],
                    is_out_of_scope: false,
                    time: this.getCurrentTime()
                }
            ];
        },

        getCurrentTime() {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        },

        renderFormattedText(text, sender) {
            if (!text) return '';
            // Safe HTML escaping
            let safe = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            // Format **bold text**
            const boldClass = sender === 'user' ? 'font-bold underline' : 'font-bold text-slate-900';
            safe = safe.replace(/\*\*(.*?)\*\*/g, `<strong class="${boldClass}">$1</strong>`);

            // Format bullet points
            safe = safe.replace(/^[*-]\s+(.*)$/gm, '• $1');

            return safe;
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            this.showTeaser = false;
            if (this.isOpen) {
                this.scrollToBottom();
                setTimeout(() => {
                    this.$refs.messageInput?.focus();
                }, 100);
            }
        },

        resetChat() {
            this.init();
            this.inputMessage = '';
            this.isLoading = false;
            this.scrollToBottom();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        submitForm() {
            const text = this.inputMessage.trim();
            if (!text || this.isLoading) return;
            this.send(text);
        },

        async send(messageText) {
            // Add user message
            this.messages.push({
                sender: 'user',
                text: messageText,
                products: [],
                is_out_of_scope: false,
                time: this.getCurrentTime()
            });

            this.inputMessage = '';
            this.isLoading = true;
            this.scrollToBottom();

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('{{ route('chatbot.message') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ message: messageText })
                });

                const data = await res.json();

                if (data.status === 'success') {
                    this.messages.push({
                        sender: 'bot',
                        text: data.reply,
                        products: data.products || [],
                        is_out_of_scope: data.is_out_of_scope || false,
                        time: this.getCurrentTime()
                    });
                } else {
                    this.messages.push({
                        sender: 'bot',
                        text: data.reply || 'Có lỗi xảy ra trong quá trình tư vấn. Bạn thử lại nhé!',
                        products: [],
                        is_out_of_scope: false,
                        time: this.getCurrentTime()
                    });
                }
            } catch (err) {
                console.error('Chatbot error:', err);
                this.messages.push({
                    sender: 'bot',
                    text: 'Xin lỗi bạn, kết nối máy chủ đang gặp trục trặc một chút. Bạn thử gửi lại câu hỏi nhé!',
                    products: [],
                    is_out_of_scope: false,
                    time: this.getCurrentTime()
                });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        viewProduct(product) {
            // Check if we are on the homepage
            const isHomePage = window.location.pathname === '/' || window.location.pathname.endsWith('/home');
            
            if (isHomePage) {
                // Dispatch view-product event to open modal
                window.dispatchEvent(new CustomEvent('view-product', {
                    detail: { id: product.id }
                }));

                // Also scroll down to products section if modal does not open
                const el = document.getElementById('san-pham-' + product.id) || document.getElementById('danh-sach-san-pham');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth' });
                }
            } else {
                // Navigate to homepage with anchor
                window.location.href = '{{ route('home') }}#san-pham-' + product.id;
            }
        }
    };
}
</script>

