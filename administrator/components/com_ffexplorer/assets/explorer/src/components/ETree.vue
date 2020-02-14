<template>
    <div class="e-folders" :style="{ height: this.treeHeight }">
        <ul class="e-folders-list">
            <ETreeItem
                v-for="item in treeData"
                :key="item.path"
                :item="item"
                @makeFolder="makeFolder"
                @addItem="addItem"
            />
        </ul>
        <vue-context ref="menu" :close-on-scroll="true">
            <li v-if="contextItem.type === 'folder'" @click="newFile">
                <a>New File</a>
            </li>
            <li v-if="contextItem.type === 'folder'" @click="newFolder">
                <a>New Folder</a>
            </li>
            <li v-if="contextItem.type === 'folder' && !isRoot" @click="renameFolder">
                <a>Rename Folder</a>
            </li>
            <li v-if="contextItem.type === 'folder' && !isRoot" @click="deleteFolder">
                <a>Delete Folder</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="openFile">
                <a>Open</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="renameFile">
                <a>Rename File</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="deleteFile">
                <a>Delete File</a>
            </li>
        </vue-context>
    </div>
</template>

<script>
import 'vue-context/src/sass/vue-context.scss';

import Vue from 'vue';
import ETreeItem from "./ETreeItem.vue";
import debounce from 'lodash/debounce';
import VueContext from 'vue-context';
import { EventBus } from '../event-bus.js';

export default {
    components: {
        ETreeItem,
        VueContext,
    },

    data() {
        return {
            treeData: [{
                name: 'root',
                path: '/',
                type: 'folder',
            }],
            treeHeight: '0px',
            contextItem: {},
        }
    },

    mounted() {
        this.$ajax({
            task: 'explorer.explodeFolder',
            path: '/',
        })
        .then(res => {
            if (res.error) {
                return alert(res.error);
            }

            const root = this.treeData.find(item => item.path === '/');

            Vue.set(root, 'children', res);
        })
        .catch(error => {
            alert('init root folder error');
            console.log(error);
        });

        this.$nextTick(() => {
            this.setTreeHeight();
        });

        window.addEventListener('resize', () => {
            this.setTreeHeight();
        });

        this.$el.addEventListener('scroll', () => {
            this.$refs.menu.close();
        });

        EventBus.$on('openContextMenu', data => {
            this.$refs.menu.open(data.event);
            Vue.set(this, 'contextItem', data.item);
        });
    },

    computed: {
        isRoot() {
            return this.contextItem.path === '/';
        }
    },

    methods: {
        refreshNode(item) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.explodeFolder',
                    path: item.path,
                })
                .then(res => {
                    if (res.error) {
                        alert(res.error)
                    } else {
                        Vue.set(item, 'children', []);
                        this.$nextTick(() => {
                            Vue.set(item, 'children', res);
                        });
                    }

                    resolve();
                })
                .catch(error => {
                    alert('refresh error');
                });
            });
        },

        newFile() {
            this.$prompt('', 'New File', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputPattern: /.+/,
                inputErrorMessage: 'Invalid Name',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doCreateNew('explorer.newFile', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'New file has been created: ' + value
                });
            }).catch(() => {});;
        },

        newFolder() {
            this.$prompt('', 'New Folder', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputPattern: /.+/,
                inputErrorMessage: 'Invalid Name',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doCreateNew('explorer.newFolder', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'New Folder has been created: ' + value
                });
            }).catch(() => {});;
        },

        renameFolder() {
            this.$prompt('Rename folder '  + this.contextItem.name, 'Rename Folder', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputValue: this.contextItem.name,
                inputPattern: /.+/,
                inputErrorMessage: 'Invalid Name',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doRename('explorer.renameFolder', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'Your folder name has been change to ' + value
                });
            }).catch(() => {});
        },

        deleteFolder() {
            this.$confirm('This will permanently delete the folder and its files. Continue?', 'Delete Folder', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doDelete('explorer.deleteFolder', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(() => {
                this.$message({
                    type: 'success',
                    message: 'Folder is deleted'
                });
            }).catch(() => {});
        },

        openFile() {
            EventBus.$emit('openFileEditor', {
                item: this.contextItem,
                force: true
            });
        },

        renameFile() {
            this.$prompt('Rename file '  + this.contextItem.name, 'Rename File', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputValue: this.contextItem.name,
                inputPattern: /.+/,
                inputErrorMessage: 'Invalid Name',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doRename('explorer.renameFile', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'Success'
                });
            }).catch(() => {});
        },

        deleteFile() {
            this.$confirm('This will permanently delete the file. Continue?', 'Delete File', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                showClose: true,
                showCancelButton: true,
                closeOnClickModal: true,
                closeOnPressEscape: true,
                closeOnHashChange: true,
                confirmButtonLoading: false,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        this.doDelete('explorer.deleteFile', instance, done);
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'File is deleted'
                });
            }).catch(() => {});
        },

        doCreateNew(task, instance, done) {
            instance.showClose = false;
            instance.showCancelButton = false;
            instance.closeOnClickModal = false;
            instance.closeOnPressEscape = false;
            instance.closeOnHashChange = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                name: instance.inputValue,
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showClose = true;
                    instance.showCancelButton = true;
                    instance.closeOnClickModal = true;
                    instance.closeOnPressEscape = true;
                    instance.closeOnHashChange = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    return this.refreshNode(this.contextItem).then(() => done());
                }
            })
            .catch(error => {});
        },

        doRename(task, instance, done) {
            instance.showClose = false;
            instance.showCancelButton = false;
            instance.closeOnClickModal = false;
            instance.closeOnPressEscape = false;
            instance.closeOnHashChange = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                newName: instance.inputValue,
                oldPath: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showClose = true;
                    instance.showCancelButton = true;
                    instance.closeOnClickModal = true;
                    instance.closeOnPressEscape = true;
                    instance.closeOnHashChange = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    const parent = this.getParent(this.treeData[0], this.contextItem.path);
                    return this.refreshNode(parent).then(() => done());
                }
            })
            .catch(error => {});
        },

        doDelete(task, instance, done) {
            instance.showClose = false;
            instance.showCancelButton = false;
            instance.closeOnClickModal = false;
            instance.closeOnPressEscape = false;
            instance.closeOnHashChange = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showClose = true;
                    instance.showCancelButton = true;
                    instance.closeOnClickModal = true;
                    instance.closeOnPressEscape = true;
                    instance.closeOnHashChange = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    const parent = this.getParent(this.treeData[0], this.contextItem.path);
                    const index = parent.children.findIndex(i => i.path === this.contextItem.path);
                    
                    parent.children.splice(index, 1);

                    return this.refreshNode(parent).then(() => done());
                }
            })
            .catch(error => {});
        },

        makeFolder(item) {
            Vue.set(item, 'children', []);
            this.addItem(item);
        },

        addItem(item) {
            item.children.push({
                name: 'new stuff'
            })
        },

        setTreeHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            this.treeHeight = (wHeight - $('.e-folders').offset().top - 35) + 'px';
        }, 100),

        getParent(root, path) {
            var node;

            root.children.some(n => {
                if (n.path === path) {
                    return node = root;
                }

                if (n.children) {
                    return node = this.getParent(n, path);
                }
            });

            return node || null;
        },
    }
}
</script>

<style lang="scss">
.e-folders {
    overflow: auto;
    border: solid 1px #ddd;

    ul.e-folders-list {
        padding: 5px;
        min-width: max-content;
        margin: 0;
        list-style: none;
        font-family: monospace;

        ul {
            margin-left: 10px;
            padding-left: 10px;
            border-left: dashed 1px #ccc;
            list-style: none;
        }
    }

    
    .v-context {
        padding: 0;

        li {
            cursor: pointer;
        }
    }
}
</style>