<template>
    <PublishContainer
        ref="container"
        name="base"
        :blueprint="blueprint"
        v-model="currentValues"
        :meta="currentMeta"
        :errors="errors"
    >
        <Header :title="title">
            <ButtonGroup>
                <Button
                    :text="__('Save & Require Re-consent')"
                    :disabled="saving"
                    @click="saveWithReconsent"
                />
                <Button
                    variant="primary"
                    :text="__('Save')"
                    :disabled="saving"
                    @click="saveNormal"
                />
            </ButtonGroup>
        </Header>

        <PublishTabs />
    </PublishContainer>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, useTemplateRef } from 'vue';
import { Header, Button, ButtonGroup, PublishContainer, PublishTabs } from '@statamic/cms/ui';
import axios from 'axios';

const props = defineProps({
    title: { type: String, required: true },
    action: { type: String, required: true },
    blueprint: { type: Object, required: true },
    meta: { type: Object, required: true },
    values: { type: Object, required: true },
});

const emit = defineEmits(['saved']);

const container = useTemplateRef('container');

const currentValues = ref(props.values);
const currentMeta = ref(props.meta);
const saving = ref(false);
const errors = ref({});

watch(() => props.values, (newValues) => {
    currentValues.value = newValues;
});

watch(() => props.meta, (newMeta) => {
    currentMeta.value = newMeta;
});

function clearErrors() {
    errors.value = {};
}

function saveWithReconsent() {
    performSave(true);
}

function saveNormal() {
    performSave(false);
}

function performSave(requireReconsent) {
    saving.value = true;
    clearErrors();

    const payload = {
        ...currentValues.value,
        require_reconsent: requireReconsent,
    };

    axios.patch(props.action, payload)
        .then(response => {
            saving.value = false;
            Statamic.$toast.success(response.data.message || __('Saved'));
            container.value.saved();
            emit('saved', response);
        })
        .catch(error => {
            saving.value = false;

            if (error.response && error.response.status === 422) {
                const { message, errors: responseErrors } = error.response.data;
                errors.value = responseErrors || {};
                Statamic.$toast.error(message || __('Validation failed. Please check the form.'));
            } else {
                const message = error.response?.data?.message;
                Statamic.$toast.error(message || __('Something went wrong'));
            }
        });
}

let saveKeyBinding;

onMounted(() => {
    saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (e) => {
        e.preventDefault();
        saveNormal();
    });
});

onUnmounted(() => saveKeyBinding.destroy());
</script>
