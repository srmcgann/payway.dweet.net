<template>
  <div class="main">
    <div class="displayModal" v-if="state.displayDepositDialog">
      <div class="container"  style="position: relative; top:50%;transform: translateY(-50%);padding:20px;font-size: .9em;">
        how much would you like to deposit?<br>
        <input type="text"  v-model="state.depositAmount" placeholder="enter deposit amount" class="numberInput">
        <br>
        <button @click="completeDeposit()">do it</button>
        <button @click="state.displayDepositDialog = false">cancel</button>
      </div>
    </div>
    <div class="container">
      Money Actions<br>
      <div class="container">
        <span class="containerIcon">+</span>
        <button class="actionButton" style="margin-left: 50px;" @click="deposit()">
          deposit
        </button>
        <button class="actionButton">
          request
        </button>
      </div>
      <div class="container">
        <span class="containerIcon">-</span>
        <button class="actionButton" style="margin-left: 50px;">
          withdraw
        </button>
        <button class="actionButton">
          send
        </button>
      </div>
      <div style="clear: both;"></div>
    </div>
    <div class="container">
      <span class="containerTitle">Balance: </span>
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
    deposit(){
      this.state.displayDepositDialog = true
    },
    completeDeposit(){
      let sendData = {userName: this.state.userName, passhash: this.state.passhash, amount: this.state.depositAmount}
      fetch(this.state.baseURL + '/deposit.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
        if(data[0]){
          this.state.getBalance()
        }
      })
    },
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
    background: #6775;
    padding: 10px;
    margin: 3px;
    color: #9ba;
    font-size: 1.2em;
    border-radius: 4px;
  }
  .containerTitle{
    font-size: 24px;
  }
  .containerIcon{
    margin: 2px;
    position: absolute;
    font-size:50px;
    background: #5778;
    color: #dfe6;
    width: 35px;
    display: inline-block;
    text-align: center;
    border-radius: 50%;
    height: 25px;
    padding-bottom: 10px;
    line-height: .63em;
  }
  .balance{
    display: inline-block;
    color: #dfe;
    font-size: 1.4em;
  }
  .numberInput{
    text-align: center;
    font-size: .9em;
    margin: 10px;
  }
  .actionButton{
    margin: 4px;
    background: #687;
    color: #ffc;
    min-width: 120px;
    width: 120px;
    font-weight: 400;
  }
  .displayModal{
    top: 0;
    left: 0;
    position: fixed;
    width: 100vw;
    height: 100vh;
    background: #123e;
    z-index: 10;
    text-align: center;
  }
</style>
