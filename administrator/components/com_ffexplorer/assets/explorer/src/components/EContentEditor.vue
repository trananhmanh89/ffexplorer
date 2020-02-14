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

        this.createEditor().then(() => {
            this.resizeEditorLayout();
        });

        setTimeout(() => {
            this.computeEditorHeight();
        });
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
                    saving: false,
                };

                model.onDidChangeContent(() => {
                    const {lastSaved, saving} = eData[path];
                    if (saving) {
                        return;
                    }

                    this.emitEditorStatus(eData[path], path);
                });

                this.emitEditorStatus(eData[path], path);
            }
            
            editor.setModel(eData[path].model);
            editor.restoreViewState(eData[path].state);
            editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S, () => {
                const currentData = eData[path];

                if (currentData.saving) {
                    return;
                }

                currentData.saving = true;
                const tmpSaved = currentData.model.getAlternativeVersionId();

                this.emitEditorStatus(currentData, path);

                const content = editor.getValue();

                this.saveContent(path, content).then(res => {
                    currentData.saving = false;

                    if (res.error) {
                        alert(res.error)
                    } else {
                        currentData.lastSaved = tmpSaved;
                    }

                    this.emitEditorStatus(currentData, path);
                })
                .catch(error => {
                    currentData.saving = false;
                    alert('save error');
                    console.log(error);
                });
            });

            editor.focus();
            
            this.current = path;
        },

        saveContent(path, content) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.saveContent',
                    content,
                    path,
                })
                .then(res => {
                    resolve(res);
                })
                .catch(error => {
                    reject(error);
                });
            });
        },

        emitEditorStatus: debounce(function(data, path) {
            let status;

            if (data.saving) {
                status = 'saving';
            } else {
                status = data.lastSaved === data.model.getAlternativeVersionId() ? 'normal' : 'dirty';
            }

            this.$emit('statusChange', {
                status,
                path,
            });
        }, 100),

        getFileContent(file) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.openFile',
                    path: file
                })
                .then(res => {
                    if (res) {
                        resolve(res);
                    } else {
                        alert('Could not open this file');
                        this.$emit('removeFile', file);
                        this.resetEditor();
                    }
                })
                .catch(error => {
                    alert('error');
                    console.log(error);
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