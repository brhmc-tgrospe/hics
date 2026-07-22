<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    divisionAreaName: {
        type: String,
    },
    roleName: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const page = usePage();

const form = useForm({
    first_name: user.first_name,
    last_name: user.last_name,
    username: user.username,
    contact_number: user.contact_number,
    email: user.email,
});

const submitProfile = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            page.props.flash.success = 'Profile information updated successfully.';
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="submitProfile"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="first_name" value="First Name" />

                <TextInput
                    id="first_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.first_name"
                    required
                    autofocus
                    autocomplete="given-name"
                />

                <InputError class="mt-2" :message="form.errors.first_name" />
            </div>

            <div>
                <InputLabel for="last_name" value="Last Name" />

                <TextInput
                    id="last_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.last_name"
                    required
                    autocomplete="family-name"
                />

                <InputError class="mt-2" :message="form.errors.last_name" />
            </div>

            <div>
                <InputLabel for="username" value="Username" />

                <TextInput
                    id="username"
                    type="text"
                    class="mt-1 block w-full disabled:opacity-50 disabled:bg-gray-100"
                    v-model="form.username"
                    :disabled="user.username_changed"
                    autocomplete="username"
                />
                <p v-if="user.username_changed" class="mt-1 text-xs text-gray-500">Username can only be edited once.</p>

                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <InputLabel for="contact_number" value="Contact Number" />

                <TextInput
                    id="contact_number"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.contact_number"
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.contact_number" />
            </div>

            <div>
                <InputLabel for="division_area" value="Division - Area" />

                <input
                    id="division_area"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm opacity-50 bg-gray-100"
                    :value="divisionAreaName"
                    disabled
                />
            </div>

            <div>
                <InputLabel for="role" value="Role" />

                <input
                    id="role"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm opacity-50 bg-gray-100"
                    :value="roleName"
                    disabled
                />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
