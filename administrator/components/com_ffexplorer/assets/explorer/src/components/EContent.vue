<template>
    <div class="e-content">
        <draggable 
            class="e-content-tabs"
            v-model="files" 
            v-bind="dragOptions"
        >
            <div 
                class="e-content-tab-item"
                v-for="file in files"
                :key="file.path"
                :class="{active: file.path === current, deleted: file.status === 'deleted'}"
                :title="file.path"
                @click="open(file)"
                @mouseup.middle="close(file.path)"
            >
                <div class="file-status is-saving" v-if="file.status === 'saving'">•</div>
                <div class="file-status is-dirty" v-if="file.status === 'dirty'">•</div>
                <div class="tab-item-title">{{file.name}}</div>
                <svg 
                    aria-hidden="true" 
                    focusable="false" 
                    data-prefix="fas" 
                    data-icon="times" 
                    class="svg-inline--fa fa-times fa-w-11" 
                    role="img" 
                    xmlns="http://www.w3.org/2000/svg" 
                    license="https://fontawesome.com/license" 
                    viewBox="0 0 352 512"
                    :style="{visibility: file.path === current ? 'visible' : 'hidden'}" 
                    @click.stop="close(file.path)"
                >
                    <path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
                </svg>
            </div>
        </draggable>
        <EContentEditor 
            ref="editor"
            @removeFile="close"
            @statusChange="onStatusChange" />
    </div>
</template>

<script>
import draggable from "vuedraggable";

import {EventBus} from '../event-bus';
import EContentEditor from "./EContentEditor.vue";

export default {
    components: {
        EContentEditor,
        draggable,
    },

    data() {
        return {
            files: [],
            dragOptions: {
                ghostClass: "ghost",
            },
            current: '',
        };
    },

    mounted() {
        EventBus.$on("openFileEditor", ({item, force}) => {
            const inList = !!this.files.find(file => file.path === item.path);

            if (!inList) {
                this.files.push({
                    name: item.name,
                    path: item.path,
                    status: 'normal',
                });
            }

            this.current = item.path;
            this.open(item, force);
        });

        EventBus.$on('fileNameChanged', (newFile, oldFile) => {
            const item = this.files.find(file => file.path === oldFile.path);

            if (item) {
                item.path = newFile.path;
                item.name = newFile.name;

                if (this.current === oldFile.path) {
                    this.current = newFile.path;
                }
            }
        });

        EventBus.$on('fileDeleted', deletedFile => {
            const item =  this.files.find(file => file.path === deletedFile.path);

            if (item) {
                item.status = 'deleted';
            }
        });

        EventBus.$on('folderNameChanged', (newFolder, oldFolder) => {
            this.files.forEach(file => {
                const idx = file.path.indexOf(oldFolder.path);

                if (idx !== 0) {
                    return;
                }

                const tail = file.path.slice(oldFolder.path.length - file.path.length);

                if (this.current === file.path) {
                    this.current = newFolder.path + tail;
                }

                file.path = newFolder.path + tail;
            });
        });
    },

    methods: {
        open(file, force) {
            this.current = file.path;
            this.$refs.editor.initEditor(file.path, force);
        },

        close(path) {
            const current = this.files.find(file => file.path === path);
            if (current.status === 'saving') {
                alert('Saving. Can not close for now.')
                return;
            }

            if (current.status === 'dirty') {
                const ok = confirm("Your content haven't save yet. Do you want to close without saving?");
                if (!ok) {
                    return;
                }
            }

            const idx = this.files.findIndex(file => file.path === path);

            if (idx !== -1) {
                this.files.splice(idx, 1);
                this.$refs.editor.removeFile(path);

                if (this.current === path) {
                    this.current = '';
                }
            }
        },

        onStatusChange({status, path}) {
            const file = this.files.find(file => file.path === path);

            if (file) {
                file.status = status;
            }
        }
    }
}
</script>

<style lang="scss">
.e-content {
    flex: 1;
    margin-left: 10px;
    overflow: hidden;

    .e-content-tabs {
        min-height: 40px;
        line-height: 15px;
        display: flex;

        .e-content-tab-item {
            position: relative;
            padding: 12px 10px;
            display: flex;
            user-select: none;
            cursor: pointer;

            .file-status {
                position: absolute;
                top: 5px;
                left: 2px;
                font-size: 18px;

                &.is-dirty {
                    color: #ff0000;
                }

                &.is-saving {
                    color:#9e9e9e;
                }
            }

            svg.fa-times {
                width: 10px;
                height: 10px;
                margin-left: 5px;
                opacity: 0.7;

                &:hover {
                    outline: solid 1px;
                }
            }

            &:hover {
                border-bottom: solid 1px rgb(199, 199, 199);

                svg {
                    visibility: visible !important;
                }
            }

            &.active {
                color: #409eff !important;
                border-bottom: solid 1px #409eff;
            }

            &.deleted {
                text-decoration: line-through;
            }
        }
    }

    .ghost {
        opacity: 0.5;
        background: #c8ebfb;
    }
}
</style>