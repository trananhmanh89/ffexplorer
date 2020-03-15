<template>
    <div class="e-sidebar">
        <div class="e-sidebar-header">
            Like my work? 
            <a href='https://ko-fi.com/I3I71FSC5' target='_blank'>
                <img 
                    height='33' 
                    style='border:0px;height:36px;' 
                    src='https://az743702.vo.msecnd.net/cdn/kofi3.png?v=2' 
                    border='0' 
                    alt='Buy Me a Coffee at ko-fi.com' />
            </a>
        </div>
        <ETree />
    </div>
</template>

<script>
import ETree from './ETree.vue';

export default {
    components: {
        ETree,
    }
}
</script>

<style lang="scss">
.e-sidebar {
    width: 300px;

    .e-sidebar-header {
        min-height: 40px;
        font-size: 14px;
        line-height: 40px;
        font-weight: bold;
        padding-bottom: 3px;
    }
}

</style>