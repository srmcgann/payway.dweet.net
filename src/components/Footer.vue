<template>
  <div class="footer">
    footer
  </div>
</template>

<script>
export default {
  name: 'Footer',
  data(){
    return{
    }
  },
  props: [ 'state' ],
  methods:{
  }
}
</script>

<style scoped>
  .footer{
    border: 1px solid white;
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
  }
</style>
