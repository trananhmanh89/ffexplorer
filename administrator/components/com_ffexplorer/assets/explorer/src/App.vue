<template>
    <div class="ffexplorer-app">
        <el-button type="primary" icon="el-icon-folder" @click="setApp('explorer')">File Manager</el-button>
        <el-button type="primary" icon="el-icon-coin" @click="setApp('database')">Database</el-button>
        <el-dialog
            top="1%"
            width="99%"
            class="ffexplorer-dialog"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :append-to-body="true"
            :visible.sync="explorerDialog"
            :before-close="handleClose"
            @opened="opened">
            <keep-alive>
                <EApp v-if="app === 'explorer'"/>
            </keep-alive>
        </el-dialog>
        <el-dialog
            top="1%"
            width="99%"
            class="ffexplorer-dialog"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :append-to-body="true"
            :visible.sync="databaseDialog"
            :before-close="handleClose"
            @opened="opened">
            <keep-alive>
                <DApp v-if="app === 'database'"/>
            </keep-alive>
        </el-dialog>
    </div>
</template>

<script>
export default {
    components: {
        EApp: () => import(/* webpackChunkName: "eapp" */ './components/EApp.vue'),
        DApp: () => import(/* webpackChunkName: "dapp" */ './components/DApp.vue'),
    },

    data() {
        return {
            explorerDialog: false,
            databaseDialog: false,
        }
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

        
    },

    computed: {
        app() {
            return this.$store.state.app;
        }
    },

    methods: {
        opened() {
            jQuery(window).trigger('resize.ffexplorer');
        },

        setApp(app) {
            if (app === 'explorer') {
                this.explorerDialog = true;
            }
            
            if (app === 'database') {
                this.databaseDialog = true;
            }

            this.$store.dispatch('setApp', app);
        },

        handleClose() {
            this.explorerDialog = false;
            this.databaseDialog = false;
            this.$store.dispatch('setApp', '');
        }
    }
}
</script>

<style lang="scss">
.container-fluid.container-main {
    padding-bottom: 0;
}

.el-message-box__input input {
    height: 30px !important;
}

.ffexplorer-dialog.el-dialog__wrapper {
    overflow: hidden;

    .el-dialog {
        margin-top: 1%;
        width: 99%;
        height: 97%;
    }

    .el-dialog__body {
        padding: 0 20px;
    }

    .el-dialog__headerbtn {
        top: 10px;
        right: 10px;
    }

    .el-dialog__header {
        padding: 0px 20px 10px;
    }
}
</style>