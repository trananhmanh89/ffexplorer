<template>
    <div class="e-folders" :style="{ height: this.treeHeight }" @contextmenu.prevent>
        <ul class="e-folders-list">
            <ETreeItem
                v-for="item in treeData"
                :key="item.path"
                :item="item"
            />
        </ul>
        <vue-context ref="menu" :close-on-scroll="true">
            <li style="border-bottom: dashed 1px #ddd;" v-if="!isRoot" @click="compress">
                <a>Compress to Zip</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="isArchive" @click="extract">
                <a>Extract to ...</a>
            </li>
            <li v-if="contextItem.type === 'folder'" @click="newFile">
                <a>New File</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="contextItem.type === 'folder'" @click="newFolder">
                <a>New Folder</a>
            </li>
            <li v-if="contextItem.type === 'folder' && !isRoot" @click="delayCall('renameFolder')">
                <a>Rename Folder</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="contextItem.type === 'folder' && !isRoot" @click="delayCall('deleteFolder')">
                <a>Delete Folder</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="delayCall('openFile')">
                <a>Open</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="contextItem.type === 'file'" @click="delayCall('download')">
                <a>Download File</a>
            </li>
            <li v-if="contextItem.type === 'file'" @click="delayCall('renameFile')">
                <a>Rename File</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="contextItem.type === 'file'" @click="delayCall('deleteFile')">
                <a>Delete File</a>
            </li>
            <li v-if="contextItem.type === 'folder'" @click="uploadDialog = true">
                <a>Upload Files</a>
            </li>
            <li style="border-bottom: dashed 1px #ddd;" v-if="contextItem.type === 'folder'" @click="refresh">
                <a>Refresh</a>
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
                        <td><input type="checkbox" v-model="chmod.userRead"></td>
                        <td><input type="checkbox" v-model="chmod.groupRead"></td>
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
        <el-dialog 
            style="text-align: center;"
            :title="this.previewImage.name" 
            :append-to-body="true"
            :visible.sync="previewImageDialog">
            <img style="box-shadow: 0px 1px 3px 0px rgba(0,0,0,0.75);" 
                :key="this.previewImage.path" 
                :src="this.previewImage.path">
        </el-dialog>
    </div>
</template>

<script>
import 'vue-context/src/sass/vue-context.scss';

import Vue from 'vue';
import ETreeItem from "./ETreeItem.vue";
import debounce from 'lodash/debounce';
import VueContext from 'vue-context';
import { EventBus } from '../event-bus';
import { arrange } from '../utils';

export default {
    components: {
        ETreeItem,
        VueContext,
    },

    data() {
        const {
            path, 
            maxFileSizeUpload,
            uploadForm
        } = FF_EXPLORER_DATA;
        const rootUri = path.root; 
        const uploadUrl = path.ajax;

        return {
            treeData: [{
                name: 'root',
                path: '\\',
                type: 'folder',
            }],
            treeHeight: '0px',
            contextItem: {},
            uploadDialog: false,
            uploadDialogBusy: false,
            uploadUrl,
            maxFileSizeUpload,
            uploadForm,
            permissionDialog: false,
            permissionLoading: false,
            chmod: {
                userRead: false,
                userWrite: false,
                userExecute: false,
                groupRead: false,
                groupWrite: false,
                groupExecute: false,
                worldRead: false,
                worldWrite: false,
                worldExecute: false,
            },
            rootUri,
            previewImageDialog: false,
            previewImage: {
                path: '',
                name: '',
            },
        }
    },

    mounted() {
        this.$ajax({
            task: 'explorer.explodeFolder',
            path: '\\',
        })
        .then(res => {
            if (res.error) {
                return alert(res.error);
            }

            const root = this.treeData.find(item => item.path === '\\');
            Vue.set(root, 'children', arrange(res));
        })
        .catch(error => {
            alert('init root folder error');
            console.log(error);
        });

        setTimeout(() => {
            this.setTreeHeight();
        });

        jQuery(window).on('resize.ffexplorer', () => {
            this.setTreeHeight();
        });

        this.$el.addEventListener('scroll', () => {
            this.$refs.menu.close();
        });

        EventBus.$on('openContextMenu', data => {
            this.$refs.menu.open(data.event);
            Vue.set(this, 'contextItem', data.item);
        });

        EventBus.$on('openImage', ({name, path}) => {
            Vue.set(this, 'previewImage', {
                path: this.rootUri + path,
                name,
            });
            
            setTimeout(() => {
                this.previewImageDialog = true;
            });
        });
    },

    computed: {
        isRoot() {
            return this.contextItem.path === '\\';
        },

        uploadData() {
            const {params} = FF_EXPLORER_DATA;
            const uploadData = {
                task: 'explorer.upload',
                path: this.contextItem.path,
            };
            
            return jQuery.extend(uploadData, params);
        },

        isArchive() {
            if (!this.contextItem.type || this.contextItem.type === 'folder') {
                return false;
            }

            const frags = this.contextItem.name.split('.');
            if (frags.length < 2) {
                return false;
            }

            if (frags.length === 2 && frags[0] === '') {
                return false;
            }

            const ext = frags.pop();
            const archiveExt = ['zip', 'tar', 'tgz', 'gz', 'gzip', 'tbz2', 'bz2', 'bzip2'];
            if (archiveExt.indexOf(ext) === -1) {
                return false;
            }
            
            return true;
        },

        permission() {
            let user = 0;
            let group = 0;
            let world = 0;

            for (const key in this.chmod) {
                const val = this.chmod[key];

                if (val) {
                    user = key === 'userRead' ? user + 4 : user;
                    user = key === 'userWrite' ? user + 2 : user;
                    user = key === 'userExecute' ? user + 1 : user;

                    group = key === 'groupRead' ? group + 4 : group;
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
        extract() {
            const source = this.contextItem.path;
            const frags = source.split('\\');
            frags.pop();
            const path = (frags.length === 1 ? '\\' : '') + frags.join('\\');

            this.$prompt('', 'Extract to ...', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                inputPattern: /^[^,:\*\?"<>|]+$/,
                inputValue: path,
                inputErrorMessage: 'Path should not contain ^ , : * ? " < > |',
                showClose: false,
                closeOnClickModal: false,
                closeOnPressEscape: false,
                closeOnHashChange: false,
                confirmButtonLoading: false,
                showCancelButton: true,
                beforeClose: (action, instance, done) => {
                    if (action == 'confirm') {
                        const target = instance.inputValue.replace('/', '\\');
                        instance.showCancelButton = false;
                        instance.confirmButtonLoading = true;

                        this.$ajax({
                            task: 'explorer.extract',
                            source,
                            target,
                        })
                        .then(res => {
                            if (res.error) {
                                return alert(res.error);
                            }

                            const item = this.findItemByPath(this.treeData[0], target);
                            if (item) {
                                return this.refreshNode(item).then(() => {
                                    done();
                                });
                            }

                            done();
                        })
                        .catch(error => {
                            alert('extract error');
                        })
                        .finally(() => {
                            instance.showCancelButton = true;
                            instance.confirmButtonLoading = false;
                        });
                    } else {
                        done();
                    }
                }
            }).then(({ value }) => {
                this.$message({
                    type: 'success',
                    message: 'Extract done.',
                });
            }).catch(() => {});;
        },

        download() {
            jQuery('.context-download-file').remove();

            const $body = jQuery('body');
            const $form = jQuery(this.uploadForm);
            $form.find('.file-path').val(this.contextItem.path);

            $body.append($form);
            $form.submit();
        },

        compress() {
            const loading = this.$loading({
                lock: true,
                text: 'Compressing',
                spinner: 'el-icon-loading',
                background: 'rgba(0, 0, 0, 0.7)',
                customClass: 'compress-loading'
            });

            this.$ajax({
                task: 'explorer.compress',
                path: this.contextItem.path,
            })
            .then(res => {
                if (res.error) {
                    alert(res.error);
                    return;
                }

                const parent = this.getParent(this.treeData[0], this.contextItem.path);
                
                return new Promise((resolve, reject) => {
                    this.refreshNode(parent).then(() => {
                        this.$message({
                            type: 'success',
                            message: 'Compress successfully'
                        });

                        resolve();
                    });
                });
            })
            .catch(error => {
                alert('error');
            })
            .finally(() => {
                loading.close();
            });
        },

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
                userRead: false,
                userWrite: false,
                userExecute: false,
                groupRead: false,
                groupWrite: false,
                groupExecute: false,
                worldRead: false,
                worldWrite: false,
                worldExecute: false,
            };

            setTimeout(() => {
                Vue.set(this, 'chmod', chmod);
            }, 300);
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
                    
                    pieces.forEach((val, i) => {
                        if (i === 0 && val === 'r') {
                            this.chmod.userRead =  true;
                        }
                        if (i === 1 && val === 'w') {
                            this.chmod.userWrite =  true;
                        }
                        if (i === 2 && val === 'x') {
                            this.chmod.userExecute =  true;
                        }
                        if (i === 3 && val === 'r') {
                            this.chmod.groupRead =  true;
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
                    })
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

        refresh() {
            const loading = this.$loading({
                lock: true,
                text: 'Refreshing',
                spinner: 'el-icon-loading',
                background: 'rgba(0, 0, 0, 0.7)',
                customClass: 'compress-loading'
            });

            this.refreshNode(this.contextItem)
            .catch(error => {
                console.log(error);
                alert('refresh error');
            })
            .finally(() => {
                setTimeout(() => {
                    loading.close();
                }, 300);
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
                        reject(res.error);
                    } else {
                        const children = item.children || [];
                        const newItems = res.filter(r => {
                            return !children.find(child => child.path === r.path );
                        });

                        newItems.forEach(i => children.push(i));

                        const deletedItems = children.filter(child => {
                            return !res.find(r => r.path === child.path);
                        });

                        deletedItems.forEach(i => {
                            const idx = children.findIndex(child => child.path === i.path);
                            children.splice(idx, 1);
                        });

                        Vue.set(item, 'children', arrange(children));
                    }

                    resolve();
                })
                .catch(error => {
                    reject(error);
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
            }).catch(() => {});
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
            }).catch(() => {});
        },

        renameFolder() {
            if (this.isLockedFolder(this.contextItem.path)) {
                alert('Folder is locked. Having some files are opening or saving. Please wait till process done then try again.');
                return;
            }

            this.$prompt('Rename folder "'+this.contextItem.name+'"', 'Rename Folder', {
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

            this.$confirm('This will permanently delete this folder and its files. Continue?', 'Delete folder "' + this.contextItem.name + '"', {
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

            this.$prompt('Rename file "'+this.contextItem.name+'"', 'Rename File', {
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

            this.$confirm('This will permanently delete this file. Continue?', 'Delete file "'+this.contextItem.name+'"', {
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
            .catch(error => {
                alert('create new error');
            });
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
            .catch(error => {
                alert('rename error');
            });
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
            .catch(error => {
                alert('delete error.');
            });
        },

        setTreeHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            this.treeHeight = (wHeight - $('.e-folders').offset().top - 35) + 'px';
        }, 100),

        getParent(root, path) {
            let node;

            root.children.some(n => {
                if (n.path === path) {
                    return node = root;
                }

                if (n.children) {
                    return node = this.getParent(n, path);
                }
            });

            return node;
        },

        findItemByPath(root, path) {
            let node;

            if (root.path === path) {
                return root;
            }

            root.children.some(n => {
                if (n.path === path) {
                    return node = n;
                }

                if (n.children) {
                    return node = this.findItemByPath(n, path);
                }
            });

            return node;
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
        min-width: 5rem;
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

.context-download-file {
    display: none;
}

.el-loading-mask.compress-loading {
    .el-icon-loading,
    .el-loading-text {
        color: #ccc;
    }
}
</style>