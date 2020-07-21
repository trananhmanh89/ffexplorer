<template>
    <div class="ffexplorer-app">
        <EApp v-if="app === 'explorer'"/>
        <DApp v-if="app === 'database'"/>
    </div>
</template>

<script>
export default {
    components: {
        EApp: () => import(/* webpackChunkName: "eapp" */ './components/EApp.vue'),
        DApp: () => import(/* webpackChunkName: "dapp" */ './components/DApp.vue'),
    },

    mounted() {
        setTimeout(() => {
            this.$notify({
                title: 'Like my work? (◕‿◕✿)',
                type: 'success',
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

        this.$store.dispatch('setApp').then(() => {
            window.addEventListener("hashchange", () => {
                this.$store.dispatch('setApp');
            });
        });
    },

    computed: {
        app() {
            return this.$store.state.app;
        }
    }
}
</script>

<style>
.container-fluid.container-main {
    padding-bottom: 0;
}

.el-message-box__input input {
    height: 30px !important;
}
</style>