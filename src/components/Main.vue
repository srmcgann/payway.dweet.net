<template>
  <div class="main">
    <div class="container">
      your money<br>
      <div class="balance" v-html="balanceString()"></div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'Main',
  components: {
  },
  data(){
    return{
      formatter: new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'})
    }
  },
  props: [ 'state' ],
  methods:{
    balanceString(){
      return this.formatter.format(this.state.userBalance/100)
    }
  }
}
</script>

<style scoped>
  .main{
    
  }
  .container{
    background: #3338;
    padding: 10px;
    margin: 10px;
  }
</style>
