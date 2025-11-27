<template>
    <publish-container
        ref="container"
        name="base"
        :blueprint="blueprint"
        v-model="currentValues"
        reference="collection"
        :meta="currentMeta"
        :errors="errors"
        v-slot="{ setFieldValue, setFieldMeta }"
    >
        <div>
            <div class="flex items-center mb-6">
                <h1 class="flex-1">{{ title }}</h1>
                <div class="btn-group">
                    <button
                        @click="saveWithReconsent"
                        class="btn"
                        :disabled="saving"
                    >
                        {{ __('Save & Require Re-consent') }}
                    </button>

                    <button
                        @click="saveNormal"
                        class="btn-primary"
                        :disabled="saving"
                    >
                        {{ __('Save') }}
                    </button>
                </div>
            </div>

            <publish-tabs
                @updated="setFieldValue"
                @meta-updated="setFieldMeta"
                :enable-sidebar="hasSidebar"
                :read-only="false" />
        </div>
    </publish-container>
</template>

<script>
export default {
    props: {
        title: String,
        action: String,
        blueprint: Object,
        meta: Object,
        values: Object
    },

    data() {
        return {
            currentValues: this.values,
            currentMeta: this.meta,
            saving: false,
            errors: {},
            hasSidebar: this.blueprint.tabs.map(tab => tab.handle).includes('sidebar'),
        }
    },

    watch: {
        values(newValues) {
            this.currentValues = newValues;
        },
        meta(newMeta) {
            this.currentMeta = newMeta;
        }
    },

    methods: {
        clearErrors() {
            this.errors = {};
        },

        saveWithReconsent() {
            this.performSave(true);
        },

        saveNormal() {
            this.performSave(false);
        },

        performSave(requireReconsent) {
            this.saving = true;
            this.clearErrors();

            const payload = {
                ...this.currentValues,
                require_reconsent: requireReconsent
            };

            this.$axios.patch(this.action, payload)
                .then(response => {
                    this.saving = false;
                    this.$toast.success(response.data.message || __('Saved'));
                    this.$refs.container.saved();
                    this.$emit('saved', response);
                })
                .catch(error => {
                    this.saving = false;
                    
                    if (error.response && error.response.status === 422) {
                        const { message, errors } = error.response.data;
                        this.errors = errors || {};
                        this.$toast.error(message || __('Validation failed. Please check the form.'));
                    } else {
                        const message = error.response?.data?.message;
                        this.$toast.error(message || __('Something went wrong'));
                    }
                });
        }
    },

    created() {
        this.$keys.bindGlobal(['mod+s'], e => {
            e.preventDefault();
            this.saveNormal();
        });
    }
}
</script>
