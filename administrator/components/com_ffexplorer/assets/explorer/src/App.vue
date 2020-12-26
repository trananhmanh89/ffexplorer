<template>
    <div class="ffexplorer-app">
        <el-button type="primary" icon="el-icon-folder" @click="setApp('explorer')">File Manager</el-button>
        <el-button type="primary" icon="el-icon-coin" @click="setApp('database')">Database</el-button>
        <el-dialog
            width="99%"
            class="ffexplorer-dialog"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :append-to-body="true"
            :visible.sync="explorerDialog"
            :before-close="handleClose"
            @opened="opened">
            <EApp v-show="app === 'explorer'"/>
        </el-dialog>
        <el-dialog
            width="99%"
            class="ffexplorer-dialog database-app-dialog"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :append-to-body="true"
            :visible.sync="databaseDialog"
            :before-close="handleClose"
            @opened="opened">
            <DApp v-show="app === 'database'"/>
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
        margin-top: 0 !important;
        width: 99%;
        height: 98%;
        top: 1%;
    }

    .el-dialog__body {
        padding: 0 20px;
    }

    .el-dialog__headerbtn {
        top: 4px;
        right: 4px;
    }

    .el-dialog__header {
        padding: 0px 20px 10px;
    }
}

.ffexplorer-dialog.database-app-dialog {
    .el-dialog__header {
        padding: 20px 20px 10px;
    }
}
</style>