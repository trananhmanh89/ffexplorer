<template>
    <div class="e-app">
        <ESidebar />
        <EContent />
    </div>
</template>

<script>
import ESidebar from './components/ESidebar.vue';
import EContent from './components/EContent.vue';
export default {
    components: {
        ESidebar,
        EContent,
    },

}
</script>

<style lang="scss">
.subhead-collapse {
    display: none;
}

.container-fluid.container-main {
    padding-bottom: 0;
}

.e-app {
    display: flex;
}

.el-message-box__input input {
    height: 30px !important;
}

</style>