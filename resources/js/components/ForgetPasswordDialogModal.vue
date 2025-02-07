<template>
    <div>
        <!-- Forget Password Dialog Modal -->
        <TransitionRoot as="template" :show="forgetPasswordDialog">
            <Dialog as="div" class="relative z-10" @close="hideForgetPasswordDialog()">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel
                                class="relative transform rounded-2xl overflow-hidden bg-white text-left shadow-xl transition-all my-8 md:my-0 w-full sm:max-w-lg md:max-w-xl">
                                <div class="bg-white p-5 sm:p-8 relative">
                                    <!-- close button -->
                                    <div class="w-9 h-9 bg-slate-100 rounded-[32px] absolute top-4 right-4 flex justify-center items-center cursor-pointer"
                                        @click="hideForgetPasswordDialog()">
                                        <XMarkIcon class="w-6 h-6 text-slate-600" />
                                    </div>
                                    <!-- end close button -->

                                    <div class="text-slate-950 text-lg sm:text-2xl font-medium leading-loose">
                                        {{ $t('Forgot Password') }}
                                    </div>

                                    <div class="text-slate-950 text-lg font-normal leading-7 tracking-tight mt-3">
                                        {{ $t('Enter you valid email or phone to reset your password') }}
                                    </div>

                                    <div class="mt-6">
                                        <label for="name" class="form-label mb-2"> {{ $t('Country') }}</label>
                                        <v-select :options="countries" label="name" :reduce="country => country.name"
                                            v-model="forgetPassword.country" placeholder="Select Country" />
                                        <span v-if="forgetErrors && forgetErrors?.country" class="text-red-500 text-sm">
                                            {{ forgetErrors?.country[0] }}
                                        </span>
                                    </div>

                                    <!-- Phone Number -->
                                    <div class="mt-4">
                                        <label
                                            class="text-slate-700 text-base font-normal leading-normal mb-2 block">
                                            {{ $t(master.forgot_otp_type == 'email' ? "Email Address" : "Phone Number")  }}
                                        </label>

                                        <div class="flex">
                                            <span v-if="master.forgot_otp_type == 'phone'"
                                                class="text-slate-700 text-base font-normal leading-normal bg-slate-100 p-2 border-y border-l rounded-tl-lg rounded-bl-lg flex items-center">
                                                +{{ forgetPassword.phone_code || '00' }}
                                            </span>
                                            <input type="text" v-model="forgetPassword.phone"
                                                :placeholder="'Enter' + (master.forgot_otp_type == 'phone' ? ' Phone Number' : ' Email Address')"
                                                class="text-base font-normal w-full p-3 placeholder:text-slate-400 border focus:border-primary outline-none"
                                                :class="[forgetErrors?.phone ? 'border-red-500' : 'border-slate-200', master.forgot_otp_type == 'phone' ? 'rounded-tr-lg rounded-br-lg' : 'rounded-lg']">
                                        </div>
                                        <span v-if="forgetErrors && forgetErrors?.phone" class="text-red-500 text-sm">{{
                                            forgetErrors?.phone[0] }}</span>
                                    </div>

                                    <!-- login button -->
                                    <button
                                        class="px-6 py-3 bg-primary mt-7 rounded-[10px] text-white text-base font-medium w-full"
                                        @click="sendForgetPasswordOtp">
                                        {{ $t('Send OTP')}}
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- OTP Dialog Modal -->
        <TransitionRoot as="template" :show="OTPDialog">
            <Dialog as="div" class="relative z-10" @close="OTPDialog = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all my-8 md:my-0 w-full sm:max-w-lg md:max-w-xl">
                                <div class="bg-white p-5 sm:p-8 relative">
                                    <!-- close button -->
                                    <div class="w-9 h-9 bg-slate-100 rounded-[32px] absolute top-4 right-4 flex justify-center items-center cursor-pointer"
                                        @click="OTPDialog = false">
                                        <XMarkIcon class="w-6 h-6 text-slate-600" />
                                    </div>
                                    <!-- end close button -->

                                    <div class="text-slate-950 text-lg sm:text-2xl font-medium leading-loose">Enter OTP
                                    </div>

                                    <div class="text-slate-950 mt-3 text-lg font-normal leading-7 tracking-tight">
                                        {{ sendMessage }} <br>
                                        {{ sendOtpEmailOrPhone }}
                                    </div>

                                    <div class="flex gap-3 mt-6">
                                        <input v-for="(input, index) in inputs" :key="index" :id="'input' + index"
                                            type="text" v-model="input.value" @input="handleInput(index)"
                                            @keydown="handleKeyDown(index, $event)" placeholder="-"
                                            class="text-base font-normal w-10 grow text-center p-3 placeholder:text-slate-400 rounded-lg border border-slate-200 focus:border-primary outline-none"
                                            maxlength="1">
                                    </div>

                                    <!-- Confirm button -->
                                    <button
                                        class="px-6 py-4 bg-primary mt-6 rounded-[10px] text-white text-base font-medium w-full"
                                        @click="verifyOTP">
                                        Confirm OTP
                                    </button>

                                    <div v-if="time > 0" class="px-4 py-2 mt-6 flex items-center justify-center gap-2">
                                        <div class="text-slate-900 text-base font-normal leading-normal">Resend code in
                                        </div>

                                        <div class="text-primary text-base font-normal leading-normal">00:{{ time }} sec
                                        </div>
                                    </div>
                                    <!-- Resend OTP -->
                                    <div v-else class="px-4 py-2 mt-6 flex items-center justify-center gap-2">
                                        <button class="text-primary text-base font-normal leading-normal"
                                            @click="sendOTP(sendOTPNumber, phoneCode)">
                                            {{ $t('Resend OTP') }}
                                        </button>
                                    </div>

                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
        <!-- end OTP dialog -->

        <!-- Reset Password Dialog Modal -->
        <TransitionRoot as="template" :show="resetPasswordDialog">
            <Dialog as="div" class="relative z-10" @close="resetPasswordDialog = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0"
                    enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" />
                </TransitionChild>
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all my-8 md:my-0 w-full sm:max-w-lg md:max-w-xl">
                                <div class="bg-white p-5 sm:p-8 relative">
                                    <!-- close button -->
                                    <div class="w-9 h-9 bg-slate-100 rounded-[32px] absolute top-4 right-4 flex justify-center items-center cursor-pointer"
                                        @click="resetPasswordDialog = false">
                                        <XMarkIcon class="w-6 h-6 text-slate-600" />
                                    </div>
                                    <!-- end close button -->

                                    <div class="text-slate-950 text-lg sm:text-2xl font-medium leading-loose">Reset
                                        Password
                                    </div>

                                    <div class="text-slate-950 text-lg font-normal leading-7 tracking-tight mt-3">Create
                                        New Password</div>

                                    <!-- Password -->
                                    <div class="mt-4">
                                        <label class="text-slate-700 text-base font-normal leading-normal mb-2 block">
                                            Create Password
                                        </label>
                                        <div class="relative">
                                            <input :type="showRegisterPassword ? 'text' : 'password'"
                                                v-model="resetPassword.password" placeholder="Enter Password"
                                                class="text-base font-normal w-full p-3 placeholder:text-slate-400 rounded-lg border  focus:border-primary outline-none"
                                                :class="registerErrors?.password ? 'border-red-500' : 'border-slate-200'">
                                            <button @click="showRegisterPassword = !showRegisterPassword">
                                                <EyeIcon v-if="showRegisterPassword"
                                                    class="w-6 h-6 text-slate-700 absolute right-4 top-1/2 -translate-y-1/2" />
                                                <EyeSlashIcon v-else
                                                    class="w-6 h-6 text-slate-700 absolute right-4 top-1/2 -translate-y-1/2" />
                                            </button>
                                        </div>
                                        <span v-if="forgetErrors && forgetErrors?.password"
                                            class="text-red-500 text-sm">
                                            {{ forgetErrors?.password[0] }}
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <label class="text-slate-700 text-base font-normal leading-normal mb-2 block">
                                            Confirm Password
                                        </label>
                                        <div class="relative">
                                            <input :type="showRegisterPassword ? 'text' : 'password'"
                                                v-model="resetPassword.password_confirmation"
                                                placeholder="Enter Password"
                                                class="text-base font-normal w-full p-3 placeholder:text-slate-400 rounded-lg border  focus:border-primary outline-none"
                                                :class="forgetErrors?.password ? 'border-red-500' : 'border-slate-200'">
                                            <button @click="showRegisterPassword = !showRegisterPassword">
                                                <EyeIcon v-if="showRegisterPassword"
                                                    class="w-6 h-6 text-slate-700 absolute right-4 top-1/2 -translate-y-1/2" />
                                                <EyeSlashIcon v-else
                                                    class="w-6 h-6 text-slate-700 absolute right-4 top-1/2 -translate-y-1/2" />
                                            </button>
                                        </div>
                                        <span v-if="conformPassError" class="text-red-500 text-sm">
                                            {{ conformPassError }}
                                        </span>
                                    </div>

                                    <!-- login button -->
                                    <button
                                        class="px-6 py-4 bg-primary mt-6 rounded-[10px] text-white text-base font-medium w-full"
                                        @click="resetPasswordSubmit">
                                        Reset Password
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
        <!-- end Registration dialog -->
    </div>
</template>

<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid'
import { nextTick, onMounted, ref, watch } from 'vue'

import { useToast } from 'vue-toastification'
import { useAuth } from '../stores/AuthStore'
import { useMaster } from '../stores/MasterStore'

const toast = useToast();
const master = useMaster();

const AuthStore = useAuth();

const hasForgetPassword = ref(false);
const resetPasswordDialog = ref(false);
const OTPDialog = ref(false);

const emits = defineEmits(['closeForget']);

const props = defineProps({
    forgetPasswordDialog: {
        type: Boolean,
        default: false
    },
    countries: {
        type: Array,
        default: []
    }
});

const forgetPassword = ref({
    phone: '',
    country: null,
    phone_code: null,
    forgot_password: true
});

watch(() => forgetPassword.value.country, () => {
    var findCountry = props.countries.find((country) => country.name == forgetPassword.value.country);
    forgetPassword.value.phone_code = findCountry?.phone_code
})

const sendOTPNumber = ref('');
const phoneCode = ref(null);
const sendOtpEmailOrPhone = ref('');
const sendMessage = ref('');

const sendOTP = (phoneNumber = '', phone_code = null) => {
    if (phoneNumber) {
        sendOTPNumber.value = phoneNumber
        phoneCode.value = phone_code
    }
    axios.post('/send-otp', {
        phone: sendOTPNumber.value,
        phone_code: phoneCode.value,
        forgot_password: hasForgetPassword.value ? 1 : null
    }).then((response) => {
        OTPDialog.value = true
        time.value = 60
        onTimer();
        toast.success(response.data.message, {
            position: "bottom-left",
        });

        hideForgetPasswordDialog()

        sendMessage.value = response.data.message
        sendOtpEmailOrPhone.value = response.data.data.email_or_phone
    }).catch((error) => {
        toast.error(error.response.data.message, {
            position: "bottom-left",
        });
    })
}

const hideForgetPasswordDialog = () => {
    emits('closeForget', false);
}

const resetPassword = ref({
    password: '',
    password_confirmation: '',
    token: ''
});

const verifyOTP = () => {
    const otp = inputs.value.map(input => input.value).join('');
    axios.post('/verify-otp', { phone: sendOTPNumber.value, otp: otp }).then((response) => {
        toast.success(response.data.message, {
            position: "bottom-left",
        });
        OTPDialog.value = false,
            resetPassword.value.token = response.data.data.token
        if (hasForgetPassword) {
            resetPasswordDialog.value = true
        }
    }).catch((error) => {
        toast.error(error.response.data.message, {
            position: "bottom-left",
        });
    })
}

const forgetErrors = ref({});

const sendForgetPasswordOtp = () => {
    hasForgetPassword.value = true;

    forgetErrors.value = {}

    if (!forgetPassword.value.country) {
        forgetErrors.value = {
            country: ['The country field is required']
        }
        return
    }

    if (!forgetPassword.value.phone) {
        forgetErrors.value = {
            phone: ['The phone field is required']
        }
        return
    }

    sendOTP(forgetPassword.value.phone, forgetPassword.value.phone_code)
}

const conformPassError = ref(null);
const resetPasswordSubmit = () => {
    if (resetPassword.value.password !== resetPassword.value.password_confirmation) {
        conformPassError.value = 'Confirm Password does not match.'
        return
    }
    axios.post('/reset-password', resetPassword.value).then((response) => {
        toast.success(response.data.message, {
            position: "bottom-left",
        });
        resetPasswordDialog.value = false;
        AuthStore.showLoginModal();
    }).catch((error) => {
        toast.error(error.response.data.message, {
            position: "bottom-left",
        });
        forgetErrors.value = error.response.data.errors
    })
}

const time = ref(60);

const onTimer = () => {
    if (time.value > 0) {
        setTimeout(() => {
            time.value -= 1;
            onTimer();
        }, 1000);
    }
}

const inputs = ref([
    { value: '' },
    { value: '' },
    { value: '' },
    { value: '' }
]);

const handleInput = (index) => {
    let nextIndex = index + 1;
    if (nextIndex < inputs.value.length && inputs.value[index].value != '') {
        nextTick(() => {
            const inputElement = document.getElementById('input' + nextIndex);
            if (inputElement) {
                inputElement.focus();
            }
        });
    }
};

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && index > 0 && inputs.value[index].value === '') {
        let previousIndex = index - 1;
        if (previousIndex >= 0) {
            nextTick(() => {
                const inputElement = document.getElementById('input' + previousIndex);
                if (inputElement) {
                    inputElement.focus();
                }
            })
        }
    }
};

</script>
