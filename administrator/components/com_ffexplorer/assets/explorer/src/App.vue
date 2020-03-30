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

    mounted() {
        setTimeout(() => {
            this.$notify({
                title: 'Like my work? (◕‿◕✿)',
                type: 'warning',
                dangerouslyUseHTMLString: true,
                message: [
                    '<a href="https://ko-fi.com/I3I71FSC5" target="_blank">',
                        '<img height="33" style="border:0px;height:36px;" src="https://az743702.vo.msecnd.net/cdn/kofi3.png?v=2" border="0" alt="Buy Me a Coffee at ko-fi.com" />',
                    '</a>',
                ].join(''),
                position: 'bottom-right',
                offset: 30,
                duration: 20000,
            });
        }, 3000);
    }
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