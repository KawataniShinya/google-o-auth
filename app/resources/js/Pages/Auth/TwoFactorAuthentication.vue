<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {onMounted, ref} from "vue";
import axios from "axios";

const form = useForm({
    two_factor_code: '',
});

const QRCode = ref('');

// QRコードを取得する関数
const getQRCode = async () => {
    try {
        const response = await axios.get('/generate-qr-code');
        QRCode.value = response.data.QR_Image;
    } catch (error) {
        console.error('Failed to fetch QR code:', error);
    }
};

// 初回読み込み時にQRコードを取得
onMounted(getQRCode);

// 認証する関数
const submit = () => {
    form.post(route('two-factor-authentication.verify'), {
        onFinish: () => form.reset('two_factor_code'),
    });
};

// ログアウトする関数
const logout = () => {
    router.post(route('logout'));
};

// シークレットをリセットする関数
const resetSecret = async () => {
    try {
        await axios.post('/reset-secret');
        // QRコードを更新するために再読み込み
        await getQRCode();
    } catch (error) {
        console.error('Failed to reset secret:', error);
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Two Factor Authentication" />

        <div v-if="QRCode" class="text-center">
            <div v-html="QRCode" class="inline-block mb-4"></div>
            <p class="mb-4">
                2要素認証には Google Authenticator アプリが必要です。
                使用しているデバイスに合わせてインストールしてください。
            </p>
            <div class="mb-4">
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=ja&gl=US" target="_blank" class="mr-4">Google Play</a>
                <a href="https://apps.apple.com/jp/app/google-authenticator/id388497605" target="_blank">App Store</a>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="two_factor_code" value="Two Factor Code" />

                <TextInput
                    id="two_factor_code"
                    type="text"
                    v-model="form.two_factor_code"
                    required
                />

                <InputError :message="form.errors.two_factor_code" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <!-- 認証 -->
                <PrimaryButton class="ms-4" :disabled="form.processing">
                    Verify
                </PrimaryButton>

                <!-- シークレットをリセットするボタン -->
                <SecondaryButton class="ms-4" @click="resetSecret" :disabled="form.processing">
                    Reset Secret
                </SecondaryButton>

                <!-- ログアウト -->
                <SecondaryButton class="ms-4" @click="logout" :disabled="form.processing">
                    Logout
                </SecondaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>

</style>
