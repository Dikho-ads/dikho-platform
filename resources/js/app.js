import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { router } from './router';
import { useApp, save } from './stores/app';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

const store = useApp(pinia);
store.$subscribe((_m, state) => save(state), { detached: true });

app.use(router);
app.mount('#app');
