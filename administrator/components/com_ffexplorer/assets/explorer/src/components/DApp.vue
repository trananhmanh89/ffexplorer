<template>
    <div class="d-app">
        <DSidebar />
        <DContent />
    </div>
</template>

<script>
import DSidebar from './DSidebar.vue';
import DContent from './DContent.vue';

export default {
    components: {
        DSidebar,
        DContent,
    }
}
</script>

<style>
.d-app {
    display: flex;
}
</style>