<template>
    <li class="e-tree-item" :class="{'e-folder': isFolder, 'e-file': !isFolder}">
        <div
            class="e-tree-item-title"
            :class="{selected: isSelected}"
            @click="onClick"
            @dblclick="onDblclick"
            @contextmenu.prevent="openContextMenu"
        >
            <span v-if="isFolder">[{{ isOpen ? '-' : '+' }}]</span> {{ item.name }}
        </div>

        <ul v-show="isOpen" v-if="isFolder">
            <ETreeItem
                v-for="child in item.children"
                :key="child.path"
                :item="child"
                @addItem="$emit('addItem', $event)"
            />
        </ul>
    </li>
</template>

<script>
import Vue from 'vue';
import { EventBus } from '../event-bus.js';

export default {
    name: 'ETreeItem',

    props: {
        item: Object
    },

    data() {
        return {
            isOpen: this.item.path === '/' ? true : false,
            fetching: false,
        }
    },

    computed: {
        isFolder() {
            return this.item.type === 'folder';
        },

        isSelected() {
            return this.item.path === this.$store.state.selectedPath;
        }
    },

    methods: {
        openContextMenu(event) {
            this.$store.commit('selectPath', this.item.path);

            EventBus.$emit('openContextMenu', {event, item: this.item});
        },

        onDblclick() {
            if (this.isFolder) {
                return;
            }

            EventBus.$emit('openFileEditor', {
                item: this.item,
                force: true
            });
        },

        onClick() {
            const path = this.isFolder ? '' : this.item.path;

            this.$store.commit('selectPath', path);

            if (this.fetching || !this.isFolder) {
                return;
            }

            this.isOpen = !this.isOpen;

            if (!this.item.children) {
                this.fetching = true;

                this.$ajax({
                    task: 'explorer.explodeFolder',
                    path: this.item.path,
                })
                .then(res => {
                    this.fetching = false;
                    Vue.set(this.item, 'children', res);
                })
                .catch(error => {
                    this.fetching = false;
                    alert('error');
                    console.log(error);
                });
            }
        },
    }
}
</script>

<style lang="scss">
.e-tree-item-title {
    white-space: nowrap;
    cursor: pointer;
    user-select: none;

    &.selected {
        background-color: #cacaca !important;
    }

    &:hover {
        background-color: #ddd;
    }
}
</style>