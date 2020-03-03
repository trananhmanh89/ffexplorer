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
            <li v-if="contextItem.type === 'folder'" @click="uploadDialog = true">
                <a>Upload Files</a>
            </li>
            <li v-if="contextItem.type === 'folder' && !isRoot" @click="delayCall('renameFolder')">
                <a>Rename Folder</a>
            </li>
            <li v-if="contextItem.type === 'folder' && !isRoot" @click="delayCall('deleteFolder')">
                <a>Delete Folder</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="delayCall('openFile')">
                <a>Open</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="delayCall('renameFile')">
                <a>Rename File</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="delayCall('deleteFile')">
                <a>Delete File</a>
            </li>
            <li v-if="!isRoot" @click="openPermissionDialog">
                <a>Permission</a>
            </li>
        </vue-context>
        <el-dialog 
            title="Upload Files" 
            :destroy-on-close="true"
            :show-close="false"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :visible.sync="uploadDialog">
            <el-upload
                class="upload-file"
                :action="uploadUrl"
                :data="uploadData"
                :on-success="onSuccessUpload"
                :multiple="true">
                <el-button size="small" type="primary">Click to upload</el-button>
                <div slot="tip" class="el-upload__tip">Files with a size less than {{maxFileSizeUpload}}B</div>
            </el-upload>
            <span slot="footer" class="dialog-footer">
                <el-button 
                    size="small"
                    :loading="uploadDialogBusy"
                    @click="onCloseUploadDialog">Close</el-button>
            </span>
        </el-dialog>
        <el-dialog 
            title="Permission" 
            width="400px"
            :destroy-on-close="true"
            :show-close="false"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :visible.sync="permissionDialog">
            <div v-loading="permissionLoading">
                <table class="permission-config">
                    <tr>
                        <td></td>
                        <td>User</td>
                        <td>Group</td>
                        <td>World</td>
                    </tr>
                    <tr>
                        <td>Read</td>
                        <td><input type="checkbox" v-model="chmod.userRread"></td>
                        <td><input type="checkbox" v-model="chmod.groupRread"></td>
                        <td><input type="checkbox" v-model="chmod.worldRead"></td>
                    </tr>
                    <tr>
                        <td>Write</td>
                        <td><input type="checkbox" v-model="chmod.userWrite"></td>
                        <td><input type="checkbox" v-model="chmod.groupWrite"></td>
                        <td><input type="checkbox" v-model="chmod.worldWrite"></td>
                    </tr>
                    <tr>
                        <td>Execute</td>
                        <td><input type="checkbox" v-model="chmod.userExecute"></td>
                        <td><input type="checkbox" v-model="chmod.groupExecute"></td>
                        <td><input type="checkbox" v-model="chmod.worldExecute"></td>
                    </tr>
                    <tr>
                        <td>Permission</td>
                        <td>{{permission.user}}</td>
                        <td>{{permission.group}}</td>
                        <td>{{permission.world}}</td>
                    </tr>
                </table>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button 
                    size="small"
                    type="primary"
                    :disabled="permissionLoading"
                    @click="savePermission">Save</el-button>
                <el-button 
                    size="small"
                    :disabled="permissionLoading"
                    @click="closePermissionDialog">Close</el-button>
            </span>
        </el-dialog>
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
        const uploadUrl = Joomla.getOptions('system.paths').base + '/index.php?option=com_ffexplorer';

        return {
            treeData: [{
                name: 'root',
                path: '/',
                type: 'folder',
            }],
            treeHeight: '0px',
            contextItem: {},
            uploadDialog: false,
            uploadDialogBusy: false,
            uploadUrl,
            maxFileSizeUpload: Joomla.getOptions('ffexplorer_max_file_size_upload'),
            permissionDialog: false,
            permissionLoading: false,
            chmod: {
                userRread: false,
                userWrite: false,
                userExecute: false,
                groupRread: false,
                groupWrite: false,
                groupExecute: false,
                worldRead: false,
                worldWrite: false,
                worldExecute: false,
            },
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

        setTimeout(() => {
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
        },

        uploadData() {
            const csrf_token = Joomla.getOptions('csrf.token');
            
            const uploadData = {
                task: 'explorer.upload',
                path: this.contextItem.path,
            };
            
            uploadData[csrf_token] = 1;

            return uploadData;
        },

        permission() {
            let user = 0;
            let group = 0;
            let world = 0;

            for (const key in this.chmod) {
                const val = this.chmod[key];

                if (val) {
                    user = key === 'userRread' ? user + 4 : user;
                    user = key === 'userWrite' ? user + 2 : user;
                    user = key === 'userExecute' ? user + 1 : user;

                    group = key === 'groupRread' ? group + 4 : group;
                    group = key === 'groupWrite' ? group + 2 : group;
                    group = key === 'groupExecute' ? group + 1 : group;

                    world = key === 'worldRead' ? world + 4 : world;
                    world = key === 'worldWrite' ? world + 2 : world;
                    world = key === 'worldExecute' ? world + 1 : world;
                }
            }

            return {user, group, world};
        },
    },

    methods: {
        savePermission() {
            this.permissionLoading = true;
            
            const {permission} = this;
            const {user, group, world} = permission;
            
            const mode = '0' + user + group + world;
            
            this.$ajax({
                task: 'explorer.setPermission',
                path: this.contextItem.path,
                mode,
            })
            .then(res => {
                if (res.error) {
                    alert(res.error);
                    return;
                }

                this.$message({
                    type: 'success',
                    message: 'Save permission successfully!',
                });
            })
            .catch(error => {
                console.log(error);
                alert('save error');
            })
            .finally(() => {
                this.permissionLoading = false;
            });
        },

        closePermissionDialog() {
            this.permissionDialog = false;

            const chmod = {
                userRread: false,
                userWrite: false,
                userExecute: false,
                groupRread: false,
                groupWrite: false,
                groupExecute: false,
                worldRead: false,
                worldWrite: false,
                worldExecute: false,
            };

            Vue.set(this, 'chmod', chmod);
        },

        openPermissionDialog() {
            this.permissionDialog = true;
            this.permissionLoading = true;
            this.$ajax({
                task: 'explorer.getPermission',
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    alert(res.error);
                    return;
                }

                if (res.permission) {
                    const pieces = res.permission.split('');
                    if (pieces.length !== 9) {
                        alert('permission error');
                    }
                    
                    for (let i = 0; i < pieces.length; i++) {
                        const val = pieces[i];
                        
                        if (i === 0 && val === 'r') {
                            this.chmod.userRread =  true;
                        }
                        if (i === 1 && val === 'w') {
                            this.chmod.userWrite =  true;
                        }
                        if (i === 2 && val === 'x') {
                            this.chmod.userExecute =  true;
                        }
                        if (i === 3 && val === 'r') {
                            this.chmod.groupRread =  true;
                        }
                        if (i === 4 && val === 'w') {
                            this.chmod.groupWrite =  true;
                        }
                        if (i === 5 && val === 'x') {
                            this.chmod.groupExecute =  true;
                        }
                        if (i === 6 && val === 'r') {
                            this.chmod.worldRead =  true;
                        }
                        if (i === 7 && val === 'w') {
                            this.chmod.worldWrite =  true;
                        }
                        if (i === 8 && val === 'x') {
                            this.chmod.worldExecute =  true;
                        }
                    }
                }
            })
            .catch(error => {
                console.log(error);
                alert('error');
            })
            .finally(() => {
                this.permissionLoading = false;
            });
        },

        onSuccessUpload(res, file, fileList) {
            if (res.error) {
                alert(res.error);

                const idx = fileList.findIndex(f => f.uid === file.uid);
                fileList.splice(idx, 1);
            }
        },

        onCloseUploadDialog() {
            this.uploadDialogBusy = true;

            this.refreshNode(this.contextItem).then(() => {
                this.uploadDialogBusy = false;
                this.uploadDialog = false;
            });
        },

        delayCall(action) {
            setTimeout(() => {
                this[action] && this[action]();
            });
        },

        isLockedFile(path) {
            const {lockedFiles} = this.$store.state;
            return lockedFiles.indexOf(path) > -1;
        },

        isLockedFolder(path) {
            const {lockedFiles} = this.$store.state;

            return lockedFiles.some(file => {
                return file.indexOf(path) === 0;
            });
        },

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
                        setTimeout(() => {
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
                inputPattern: /^[^,\\\/:\*\?"<>|]+$/,
                inputErrorMessage: 'File name should not contain ^ , \\ / : * ? " < > |',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
                inputPattern: /^[^,\\\/:\*\?"<>|]+$/,
                inputErrorMessage: 'Folder name should not contain ^ , \\ / : * ? " < > |',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
            if (this.isLockedFolder(this.contextItem.path)) {
                alert('Folder is locked. Having some files are opening or saving. Please wait till process done then try again.');
                return;
            }

            this.$prompt('Rename folder '  + this.contextItem.name, 'Rename Folder', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputValue: this.contextItem.name,
                inputPattern: /^[^,\\\/:\*\?"<>|]+$/,
                inputErrorMessage: 'Folder name should not contain ^ , \\ / : * ? " < > |',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
            if (this.isLockedFolder(this.contextItem.path)) {
                alert('Folder is locked. Having some files are opening or saving. Please wait till process done then try again.');
                return;
            }

            this.$confirm('This will permanently delete the folder and its files. Continue?', 'Delete Folder', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
            if (this.isLockedFile(this.contextItem.path)) {
                alert('File is locked for opening or saving. Please try again later.');
                return;
            }

            EventBus.$emit('openFileEditor', {
                item: this.contextItem,
                force: true
            });
        },

        renameFile() {
            if (this.isLockedFile(this.contextItem.path)) {
                alert('File is locked for opening or saving. Please try again later.');
                return;
            }

            this.$prompt('Rename file '  + this.contextItem.name, 'Rename File', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputValue: this.contextItem.name,
                inputPattern: /^[^,\\\/:\*\?"<>|]+$/,
                inputErrorMessage: 'File name should not contain ^ , \\ / : * ? " < > |',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
            if (this.isLockedFile(this.contextItem.path)) {
                alert('File is locked for opening or saving. Please try again later.');
                return;
            }

            this.$confirm('This will permanently delete the file. Continue?', 'Delete File', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
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
            instance.showCancelButton = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                name: instance.inputValue,
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showCancelButton = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    return this.refreshNode(this.contextItem).then(() => done());
                }
            })
            .catch(error => {});
        },

        doRename(task, instance, done) {
            instance.showCancelButton = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                newName: instance.inputValue,
                oldPath: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showCancelButton = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    const parent = this.getParent(this.treeData[0], this.contextItem.path);

                    this.refreshNode(parent).then(() => {
                        const eventName = this.contextItem.type === 'file' ? 'fileNameChanged' : 'folderNameChanged';

                        EventBus.$emit(eventName, res.data, this.contextItem);

                        done();
                    });

                    
                }
            })
            .catch(error => {});
        },

        doDelete(task, instance, done) {
            instance.showCancelButton = false;
            instance.confirmButtonLoading = true;

            this.$ajax({
                task: task,
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    instance.showCancelButton = true;
                    instance.confirmButtonLoading = false;
                    alert(res.error);
                } else {
                    const parent = this.getParent(this.treeData[0], this.contextItem.path);
                    const index = parent.children.findIndex(i => i.path === this.contextItem.path);
                    
                    parent.children.splice(index, 1);

                    return this.refreshNode(parent).then(() => { 
                        const eventName = this.contextItem.type === 'file' ? 'fileDeleted' : 'folderDeleted';

                        EventBus.$emit(eventName, this.contextItem);
                        done();
                    });
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
            user-select: none;
        }
    }

    .permission-config {
        width: 100%;

        td {
            padding: 8px;
            line-height: 18px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }
    }
}
</style>