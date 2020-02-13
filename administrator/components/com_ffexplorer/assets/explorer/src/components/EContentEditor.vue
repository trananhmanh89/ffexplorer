<template>
    <div class="e-content-editor" :style="{height: editorHeight}"></div>
</template>

<script>
import {EventBus} from '../event-bus';
import debounce from 'lodash/debounce';

let editor = false;
const eData = {};

export default {
    data() {
        return {
            editorHeight: '200px',
            current: '',
        }
    },

    mounted() {
        window.addEventListener('resize', () => {
            this.computeEditorHeight();
            this.resizeEditorLayout();
        });

        setTimeout(() => {
            this.computeEditorHeight()
        });
    },

    methods: {
        async initEditor(file) {
            if (!editor) {
                await this.createEditor();
            }

            if (this.current) {
                const currentData = {
                    model: editor.getModel(),
                    state: editor.saveViewState(),
                };

                eData[this.current] = currentData;
            }

            if (!eData[file]) {
                const {data} = await this.getFileContent(file);
                const model = monaco.editor.createModel(data.content, data.language);

                eData[file] = {
                    model: model,
                    state: {},
                    lastSaved: model.getAlternativeVersionId(),
                };

                model.onDidChangeContent(() => {
                    const {lastSaved} = eData[file];
                    const dirty = lastSaved !== model.getAlternativeVersionId();

                    this.$emit('dirty', {dirty, file});
                });
            }
            
            editor.setModel(eData[file].model);
            editor.restoreViewState(eData[file].state);
            
            this.current = file;
        },

        getFileContent(file) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.openFile',
                    path: file
                })
                .then(data => {
                    if (data) {
                        resolve(data);
                    } else {
                        alert('Could not open this file');
                        this.$emit('removeFile', file);
                        this.resetEditor();
                    }
                });
            });
        },

        createEditor() {
            return new Promise((resolve, reject) => {
                window.require(['vs/editor/editor.main'], () => {
                    editor = monaco.editor.create(this.$el, { model: null });
                    resolve();
                });
            });
        },

        removeFile(file) {
            delete eData[file];

            if (this.current === file) {
                this.resetEditor();
            }
        },

        resetEditor() {
            editor && editor.dispose();
            editor = '';
            this.current = '';
        },

        resizeEditorLayout: debounce(function() {
            editor && editor.layout();
        }, 100),

        computeEditorHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            this.editorHeight = (wHeight - $('.e-folders').offset().top - 35) + 'px';
        }, 100),
    }
}
</script>

<style lang="scss">
.e-content-editor {
    border: solid 1px #ddd;
}
</style>