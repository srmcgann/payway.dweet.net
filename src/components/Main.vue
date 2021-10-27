<template>
  <div class="main">
    <div class="displayModal" v-if="state.displayStatus" style="z-index: 100;" ref="statusContainer"></div>
    <div class="displayModal" v-if="state.displayTransactionDialog">
      <div class="container"  style="position: relative; top:50%;transform: translateY(-50%);padding:20px;font-size: .9em;">
        how much would you like to
        <span v-if="curTransType == 'deposit'">deposit</span>
        <span v-if="curTransType == 'withdrawal'">withdraw</span>
        <span v-if="curTransType == 'request'">request</span>
        <span v-if="curTransType == 'send'">send</span>
        ?<br>
        <input
          @input="validateCurrency()"
          v-on:keyup.enter="completeTransaction()"
          ref="transactionInput"
          type="text"
          v-model="state.transactionAmount"
          :placeholder="'enter ' + curTransType + ' amount'"
          class="numberInput">
        <br><br><br>
        <div v-if="curTransType == 'request' || curTransType == 'send'">
          <span v-if="curTransType == 'request'">From whom would you like <br>to receive the funds?<br></span>
          <span v-if="curTransType == 'send'">To whom would you like <br>to send funds?<br></span><br>
          <input
            type="text"
            v-model="state.transactionPartner"
            @input="validateUser()"
            ref="transactionPartnerInput"
            placeholder="enter a username"
            class="userNameInput"
            v-on:keyup.enter="completeTransaction()"
          ><br>
          <span
            style="font-size: 20px;"
            :style="'color:' + (userNameAvailable ? '#5f5' : '#f55')"
            v-html="(state.transactionPartner && !userNameValidationInProgress) ? (userNameAvailable ? 'user found!' : 'user not found  &nbsp;&nbsp;&nbsp; :(') : '&nbsp;'"
          ></span><br><br>
        </div>
        <button @click="completeTransaction()">do it</button>
        <button @click="state.displayTransactionDialog = false">cancel</button>
      </div>
    </div>
    <div class="container">
      Money Actions<br>
      <div class="container">
        <span class="containerIcon">+</span>
        <button class="actionButton" style="margin-left: 50px;" @click="deposit()">
          deposit
        </button>
        <button class="actionButton" @click="request()">
          request
        </button>
      </div>
      <div class="container">
        <span class="containerIcon">-</span>
        <button class="actionButton" style="margin-left: 50px;" @click="withdraw()">
          withdraw
        </button>
        <button class="actionButton" @click="send()">
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
      curTransType: '',
      userNameAvailable: true,
      userNameValidationInProgress: false,
      formatter: new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'})
    }
  },
  props: [ 'state' ],
  methods:{
    validateUser(){
      this.userNameAvailable = true
      this.userNameValidationInProgress = true
      let userName = this.$refs.transactionPartnerInput.value
      let sendData = {userName}
      fetch(this.state.baseURL + '/checkUserNameAvailability.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(res => res.json())
      .then(data => {
        this.userNameValidationInProgress = false
        this.userNameAvailable = !!!data
      })
    },
    deposit(){
      this.curTransType = 'deposit'
      this.state.displayTransactionDialog = true
      this.$nextTick(()=>this.$refs.transactionInput.focus())
    },
    withdraw(){
      this.curTransType = 'withdrawal'
      this.state.displayTransactionDialog = true
      this.$nextTick(()=>this.$refs.transactionInput.focus())
    },
    request(){
      this.curTransType = 'request'
      this.state.displayTransactionDialog = true
      this.$nextTick(()=>this.$refs.transactionInput.focus())
    },
    send(){
      this.curTransType = 'send'
      this.state.displayTransactionDialog = true
      this.$nextTick(()=>this.$refs.transactionInput.focus())
    },
    validateCurrency(e){
      let el = this.$refs.transactionInput
      let val = el.value
      val = val.split('').filter(v=>{
        return v.charCodeAt(0) < 58 && v.charCodeAt(0) > 47 //|| (v.charCodeAt(0) == 46)
      }).join('')
      val = this.formatter.format(val/100)
      this.state.transactionAmount = val
      el.value = val
    },
    completeTransaction(){
      let sendData = {
        userName: this.state.userName,
        passhash: this.state.passhash,
        amount: this.state.transactionAmount,
        transactionType: this.curTransType,
        transactionPartner: this.state.transactionPartner
      }
      fetch(this.state.baseURL + '/transact.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
        console.log(data)
        if(data[0]){
          this.state.userBalance = data[1]
          this.state.displayStatus = true
          this.$nextTick(()=>{
            this.$refs.statusContainer.innerHTML = '<div style="position: relative; top: 50%; transform: translate(0, -50%); font-size: 1.5em;">' + this.curTransType + ' complete.</div>'
            this.state.displayTransactionDialog = false
          })
          setTimeout(()=>{
            this.state.displayStatus = false
            this.state.transactionAmount = ''
            this.state.transactionPartner = ''
          }, 1000)
        }
      })
    },
    balanceString(){
      return '<span style="color: ' + (this.state.userBalance < 0 ? '#f66' : '#5fa') + '">' + this.formatter.format(this.state.userBalance/100) + '</span>'
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
    font-size: 1.4em;
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
  .userNameInput{
    text-align: center;
  }
</style>
