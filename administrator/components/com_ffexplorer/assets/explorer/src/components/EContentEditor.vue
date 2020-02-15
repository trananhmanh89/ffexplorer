<template>
    <div class="e-content-editor" :style="{height: editorHeight}"></div>
</template>

<script>
import {EventBus} from '../event-bus';
import debounce from 'lodash/debounce';

let editor = false;
const eData = {};

window.eData = eData;

export default {
    data() {
        return {
            editorHeight: '0px',
            current: '',
        }
    },

    mounted() {
        EventBus.$on('fileNameChanged', (newFile, oldFile) => {
            if (eData[oldFile.path]) {
                eData[newFile.path] = eData[oldFile.path];
                delete eData[oldFile.path];

                if (this.current === oldFile.path) {
                    this.current = newFile.path;
                }
            }
        });

        EventBus.$on('folderNameChanged', (newFolder, oldFolder) => {
            for (const key in eData) {
                const idx = key.indexOf(oldFolder.path);

                if (idx !== 0) {
                    return;
                }

                const tail = key.slice(oldFolder.path.length - key.length);
                const newkey = newFolder.path + tail;

                eData[newkey] = eData[key];
                delete eData[key];

                if (this.current === key) {
                    this.current = newkey;
                }
            }
        });

        window.addEventListener('resize', () => {
            this.computeEditorHeight();
            this.resizeEditorLayout();
        });

        this.createEditor().then(() => {
            this.resizeEditorLayout();
        });

        this.$nextTick(() => {
            this.computeEditorHeight();
        });
    },

    methods: {
        async initEditor(path, force) {
            if (!editor) {
                await this.createEditor();
            }

            if (this.current) {
                eData[this.current] = eData[this.current] || {};
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

                this.emitEditorStatus(eData[path], path);
            }
            
            editor.setModel(eData[path].model);
            editor.restoreViewState(eData[path].state);
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
                    if (!res || res.error) {
                        alert(res ? res.error : 'Could not open file');
                        this.$emit('removeFile', file);
                        this.resetEditor();
                    } else {
                        resolve(res);
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

                    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S, () => {
                        const currentData = eData[this.current];

                        if (!currentData || currentData.saving) {
                            return;
                        }

                        currentData.saving = true;
                        const tmpSaved = currentData.model.getAlternativeVersionId();

                        this.emitEditorStatus(currentData, this.current);

                        const content = editor.getValue();

                        this.saveContent(this.current, content).then(res => {
                            currentData.saving = false;

                            if (res.error) {
                                alert(res.error)
                            } else {
                                currentData.lastSaved = tmpSaved;
                            }

                            this.emitEditorStatus(currentData, this.current);
                        })
                        .catch(error => {
                            currentData.saving = false;
                            alert('save error');
                            console.log(error);
                        });
                    });

                    editor.onDidChangeModelContent(() => {
                        const currentData = eData[this.current];

                        if (!currentData) {
                            return;
                        }

                        const {lastSaved, saving} = currentData;
                        if (saving) {
                            return;
                        }

                        this.emitEditorStatus(currentData, this.current);
                    });

                    resolve();
                });
            });
        },

        removeFile(path) {
            delete eData[path];

            if (this.current === path) {
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