import ConsentManagerPublishForm from './components/ConsentManagerPublishForm.vue';

Statamic.booting(() => {
    Statamic.$components.register('consent-manager-publish-form', ConsentManagerPublishForm);
});
