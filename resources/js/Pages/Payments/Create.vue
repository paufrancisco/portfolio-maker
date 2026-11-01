<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    portfolio: Object,
    amount: Number,
    qrUrl: String,
    walletLabel: String,
    payment: Object,
});

const form = useForm({
    reference_no: '',
    proof: null,
});

const submit = () => {
    form.post(route('payments.store', props.portfolio.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Payment" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Payment</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                    <p class="text-sm text-gray-600">Scan to pay via {{ walletLabel }}</p>
                    <img :src="qrUrl" alt="Payment QR" class="mx-auto my-4 max-h-80" />
                    <p class="text-2xl font-bold text-gray-800">PHP {{ amount.toFixed(2) }}</p>
                </div>

                <div
                    v-if="payment && payment.status === 'pending'"
                    class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800"
                >
                    Payment received (ref: {{ payment.reference_no }}). Waiting for review.
                </div>

                <form
                    v-else
                    @submit.prevent="submit"
                    class="space-y-4 rounded-lg bg-white p-6 shadow-sm"
                >
                    <div>
                        <InputLabel for="reference_no" value="Reference number" />
                        <TextInput id="reference_no" v-model="form.reference_no" class="mt-1 block w-full" />
                        <InputError :message="form.errors.reference_no" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="proof" value="Proof of payment (screenshot or PDF)" />
                        <input
                            id="proof"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="mt-1 block w-full text-sm text-gray-700"
                            @input="form.proof = $event.target.files[0]"
                        />
                        <InputError :message="form.errors.proof" class="mt-2" />
                    </div>

                    <PrimaryButton :disabled="form.processing || !form.proof">
                        {{ form.processing ? 'Submitting...' : 'Submit payment' }}
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
