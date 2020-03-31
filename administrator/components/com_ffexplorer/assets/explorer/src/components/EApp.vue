<template>
    <div class="e-app">
        <ESidebar />
        <EContent />
    </div>
</template>

<script>
import ESidebar from './ESidebar.vue';
import EContent from './EContent.vue';

export default {
    components: {
        ESidebar,
        EContent,
    },
}
</script>

<style>
.e-app {
    display: flex;
}
</style>