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
                :class="{active: file.path === current}"
                :title="file.path"
                @click="open(file)"
            >
                <div class="is-dirty" v-if="file.status === 'dirty'">•</div>
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
                    @click.stop="removeFile(file.path)"
                >
                    <path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
                </svg>
            </div>
        </draggable>
        <EContentEditor 
            ref="editor"
            @removeFile="removeFile"
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
        EventBus.$on("openFileEditor", data => {
            if (this.files.find(file => file.path === data.path)) {
            } else {
                this.files.push({
                    name: data.name,
                    path: data.path,
                    status: 'normal',
                });
            }

            this.current = data.path;
            this.open(data);
        });
    },

    methods: {
        open(file) {
            this.current = file.path;
            this.$refs.editor.initEditor(file.path);
        },

        removeFile(path) {
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
        margin: 12px 0;
        min-height: 16px;
        line-height: 15px;
        display: flex;

        .e-content-tab-item {
            position: relative;
            margin: 0 5px;
            padding: 0 5px;
            display: flex;
            cursor: pointer;
            user-select: none;

            .is-dirty {
                position: absolute;
                top: -8px;
                left: -2px;
                color: #ff0000;
                font-size: 18px;
            }

            svg.fa-times {
                width: 10px;
                height: 10px;
                margin-left: 3px;
                opacity: 0.7;

                &:hover {
                    outline: solid 1px;
                }
            }

            &:hover svg{
                visibility: visible !important;
            }

            &.active {
                color: #409eff;
                border-bottom: solid 1px #409eff;
            }
        }
    }

    .ghost {
        opacity: 0.5;
        background: #c8ebfb;
    }
}
</style>