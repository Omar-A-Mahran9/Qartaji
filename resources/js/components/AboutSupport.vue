<template>
    <div class="main-container mt-12">
        <div class="p-6 bg-white rounded-xl shadow-lg border border-dashed border-slate-300 grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-16">
            <div class="flex justify-start items-start gap-4">
                <img :src="'/assets/icons/secure-payment.svg'" class="w-11 h-11" loading="lazy" alt="icon"/>
                <div class="text-slate-600 text-sm font-medium leading-tight">
                    <div class=" text-slate-950 text-base font-bold leading-normal">
                        {{ $t('Secure Payment Gateways') }}
                    </div>
                    <div class="text-slate-700 text-base font-normal leading-normal mt-1">
                        {{ $t('48+ gateways to ensure your security') }}.
                    </div>
                </div>
            </div>

            <div class="flex justify-start items-start gap-4">
                <img :src="'/assets/icons/customer-review.svg'" class="w-11 h-11" loading="lazy" alt="icon"/>
                <div class="text-slate-600 text-sm font-medium leading-tight">
                    <div class=" text-slate-950 text-base font-bold leading-normal">
                        {{ $t('Genuine Customer Reviews') }}
                    </div>
                    <div class="text-slate-700 text-base font-normal leading-normal mt-1">
                        {{ $t('Find verified reviews showcased on our platforms') }}.
                    </div>
                </div>
            </div>

            <div class="flex justify-start items-start gap-4">
                <img :src="'/assets/icons/support.svg'" class="w-11 h-11" loading="lazy" alt="icon"/>
                <div class="text-slate-600 text-sm font-medium leading-tight">
                    <div class=" text-slate-950 text-base font-bold leading-normal">
                       {{ $t('24/7 Customer Support') }}
                    </div>
                    <div class="text-slate-700 text-base font-normal leading-normal mt-1">
                        {{ $t('Always our support team is available for you') }}.
                    </div>
                </div>
            </div>

            <div class="flex justify-start items-start gap-4">
                <img :src="'/assets/icons/easy-return.svg'" class="w-11 h-11" loading="lazy" alt="icon"/>
                <div class="text-slate-600 text-sm font-medium leading-tight">
                    <div class=" text-slate-950 text-base font-bold leading-normal">
                        {{ $t('Easy Return Policy') }}
                    </div>
                    <div class="text-slate-700 text-base font-normal leading-normal mt-1">
                        {{ $t("If you're not satisfied, return it hassle-free") }}.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
