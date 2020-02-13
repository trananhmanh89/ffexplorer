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

        this.createEditor();
    },

    methods: {
        async initEditor(path, force) {
            if (!editor) {
                await this.createEditor();
            }

            if (this.current) {
                eData[this.current].model = editor.getModel();
                eData[this.current].state = editor.saveViewState();
            }

            if (!eData[path] || force) {
                const {data} = await this.getFileContent(path);
                const model = monaco.editor.createModel(data.content, data.language);

                eData[path] = {
                    model: model,
                    state: {},
                    lastSaved: model.getAlternativeVersionId(),
                };

                model.onDidChangeContent(() => {
                    const {lastSaved} = eData[path];
                    const status = lastSaved === model.getAlternativeVersionId() ? 'normal' : 'dirty';

                    this.emitEditorStatus({
                        status, 
                        path,
                    });
                });

                this.emitEditorStatus({
                    status: 'normal', 
                    path,
                });
            }
            
            editor.setModel(eData[path].model);
            editor.restoreViewState(eData[path].state);
            editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S, function() {
                console.log(path);
            });

            editor.focus();
            
            this.current = path;
        },

        emitEditorStatus: debounce(function(payload) {
            this.$emit('statusChange', payload);
        }, 100),

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
                    if (!editor) {
                        editor = monaco.editor.create(this.$el, {
                            model: null,
                        });
                    }

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