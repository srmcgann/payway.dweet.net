<template>
  <div class="footer">
    PayWay - the better way to pay online &nbsp;&nbsp;&bull;&nbsp;&nbsp;
    contact: <a href="mailto:whitehotrobot@gmail.com">whitehotrobot@gmail.com</a>
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
    position: fixed;
    bottom: 0;
    background: linear-gradient(90deg, #f004, #00f8);
    height: 25px;
    color: #fff;
    padding-top: 5px;
    text-shadow: 2px 2px 2px #000;
    width: 100%;
    text-align: center;
  }
</style>
