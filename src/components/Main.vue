<template>
  <div class="main">
    <div class="displayModal" v-if="state.displayStatus" style="z-index: 100;" ref="statusContainer"></div>
    <div class="displayModal" v-if="state.displayTransactionDialog">
      <div class="container"  style="position: relative; top:50%;transform: translateY(-50%);padding:20px;font-size: .9em;">
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
          ></span>
          <br><br>
        </div>
        how much would you like to
        <span v-if="curTransType == 'deposit'">deposit?</span>
        <span v-if="curTransType == 'withdrawal'">withdraw?</span>
        <span v-if="curTransType == 'request'">request?</span>
        <span v-if="curTransType == 'send'">send?</span>
        <br>
        (current balance: <span :style="'display: inline-block;padding-left: 5px; padding-right: 5px;color: ' + (state.userBalance < 0 ? '#f88' : '#8fc') + ';background: ' + (state.userBalance < 0 ? '#411' : '#142') ">{{formatter.format(state.userBalance/100)}}</span>)<br>
        <input
          @input="validateCurrency()"
          v-on:keyup.enter="completeTransaction()"
          ref="transactionInput"
          type="text"
          v-model="state.transactionAmount"
          :placeholder="'enter ' + curTransType + ' amount'"
          class="numberInput"><br>
        <button @click="completeTransaction()">do it</button>
        <button @click="state.displayTransactionDialog = false">cancel</button>
      </div>
    </div>
    <div class="container">
      Money Actions<br>
      <div class="container">
        <span class="containerIcon credit">+</span>
        <button class="actionButton" style="margin-left: 50px;" @click="deposit()">
          deposit
        </button>
        <button class="actionButton" @click="request()">
          request
        </button>
      </div>
      <div class="container">
        <span class="containerIcon debit">-</span>
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
      <span class="containerTitle">Current Balance &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
      <div class="balance" v-html="balanceString()"></div>
    </div>
    <div class="container" style="padding-top: 0;" v-if="state.userHistory">
      Activity
      <div class="pagination">
        <button
          :disabled="!state.historyPage"
          title="jump to first page"
          class="navButton"
          :class="{'enabled': state.historyPage, 'disabled': !state.historyPage}"
          @click="state.historyPage=0; state.getBalance()"
        >
          &laquo;
        </button>
        <button
          :disabled="!state.historyPage"
          title="previous page"
          class="navButton"
          :class="{'enabled': state.historyPage, 'disabled': !state.historyPage}"
          @click="state.historyPage=Math.max(0, state.historyPage-1); state.getBalance()"
        >&larr;</button>
        page {{state.historyPage + 1}} of {{state.pages}}
        <button
          :disabled="state.historyPage == state.pages - 1"
          title="next page"
          class="navButton"
          :class="{'enabled': state.historyPage < state.pages - 1, 'disabled': state.historyPage == state.pages - 1}"
          @click="state.historyPage=Math.min(state.pages-1, state.historyPage+1); state.getBalance()"
          >&rarr;</button>
        <button
          :disabled="state.historyPage == state.pages - 1"
          title="jump to last page"
          class="navButton"
          :class="{'enabled': state.historyPage < state.pages - 1, 'disabled': state.historyPage == state.pages - 1}"
          @click="state.historyPage=state.pages-1; state.getBalance()"
        >
          &raquo;
        </button>
      </div>
      <label for="itemsPerPage" style="display: inline-block;font-size: 12px;line-height: 1em;margin-left: 300px;position:absolute;margin-top: 5px;">
        <select v-model="state.transactionsPerPage" id="itemsPerPage" @input="updatePrefs('transactionsPerPage')" ref="transactionsPerPage">
          <option v-for="i = 1 in 20" v-html="i"></option>
        </select>
        <div style="display: inline-block; margin-left: 3px;position:absolute;margin-top:-1px;width: 50px;line-height: .8em">items<br>per page</div>
      </label>
      <div v-for="item in state.userHistory" class="container history" :key="item.id">
        <div style="width: 50%; min-width: 600px;">
          <table>
            <tr>
              <td class="tdLeft">
                <span class="avatarSmall" :style="'background-image: url(' + (item.related ? (item.related.userAvatar ? item.related.userAvatar : state.defaultAvatar) : state.userAvatar)+')!important'"></span>
              </td>
              <td class="tdRight"><span style="color: #48c;font-weight: 900;font-size: 1.5em;">{{verbiage(item.type)}}</span>
                <span v-if="+item.relatedTransactionID">
                  <br>
                  {{item.related.type=='sent' ? '(from ' + item.related.userName + ' to ' + state.userName + ')': ''}}
                  {{item.related.type=='receive' ? '(from ' + state.userName + ' to ' + item.related.userName + ')': ''}}
                  {{item.related.type=='send' ? '(from ' + item.related.userName + ' to ' + state.userName + ')': ''}}
                  {{item.related.type=='request' ? '(from ' + state.userName + ' to ' + item.related.userName + ')': ''}}
                </span>
              </td>
            </tr>
            <tr>
              <td class="tdLeft"><span class="historyListItem">Amt</span></td>
              <td class="tdRight balance">
                <span v-if="item.type=='request' || item.type == 'receive'">
                  <span class="containerIconSmall credit">+</span>
                </span>
                <span v-if="item.type=='deposit'">
                  <span class="containerIconSmall credit">+</span>
                </span>
                <span v-if="item.type=='withdrawal'">
                  <span class="containerIconSmall debit">-</span>
                </span>
                <span v-if="item.type=='sent' || item.type == 'send'">
                  <span class="containerIconSmall debit">-</span>
                </span>
                <span style="margin-left: 20px;font-size: 1.1em;">{{formatter.format(item.amount/100)}}</span>
              </td>
            </tr>
          </table>
          <table>
            <tr>
              <td class="tdLeft"><span class="historyListItem">Date</span></td>
              <td class="tdRight">{{formatDate(item.date)}}</td>
            </tr>
            <tr>
              <td class="tdLeft"><span class="historyListItem">Time</span></td>
              <td class="tdRight">{{formatDate(item.time)}}</td>
            </tr>
            <tr>
              <td class="tdLeft"><span class="historyListItem">Balance</span></td>
              <td class="tdRight" :style="'display: inline-block;padding-left: 5px; padding-right: 5px;color: ' + (item.balance < 0 ? '#f88' : '#8fc') + ';background: ' + (item.balance < 0 ? '#411' : '#142') ">{{formatter.format(item.balance/100)}}</td>
            </tr>
          </table>
          <div style="clear: both"></div>
        </div>
      </div>
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
    verbiage(type){
      switch(type){
        case 'send': return 'sent funds'; break
        case 'receive': return 'received funds'; break
        case 'request': return 'request fulfilled'; break
        case 'deposit': return 'deposited funds'; break
        case 'withdrawal': return 'funds withdrawal'; break
      }
    },
    updatePrefs(pref){
      let sendData = {
        userName: this.state.userName,
        passhash: this.state.passhash,
        pref,
        newval: this.$refs[pref].value,
      }
      fetch(this.state.baseURL + '/updatePrefs.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
      })
    },
    formatDate(date){
      return date//(new Date(Date.parse(date)))
    },
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
      this.$nextTick(()=>this.$refs.transactionPartnerInput.focus())
    },
    send(){
      this.curTransType = 'send'
      this.state.displayTransactionDialog = true
      this.$nextTick(()=>this.$refs.transactionPartnerInput.focus())
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
            this.state.historyPage = 0
          }, 1000)
        }
      })
    },
    balanceString(){
      return '<span style="padding-left: 10px; padding-right: 10px; color: ' + (this.state.userBalance < 0 ? '#f88' : '#8fc') + ';background: ' + (this.state.userBalance < 0 ? '#411' : '#142') + '">' + this.formatter.format(this.state.userBalance/100) + '</span>'
    }
  }
}
</script>

<style scoped>
  .main{
    margin-bottom: 40px;
    margin-top:90px;
    height: 100%;
  }
  .container{
    background: #5683;
    padding: 6px;
    padding-top: 3px;
    padding-bottom: 5px;
    margin: 8px;
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
    color: #dfe6;
    width: 35px;
    display: inline-block;
    text-align: center;
    border-radius: 50%;
    height: 25px;
    padding-bottom: 10px;
    line-height: .63em;
  }
  .containerIconSmall{
    margin: 0px;
    margin-top: -5px;
    margin-left: -10px;
    font-size:50px;
    background: #5778;
    color: #dfe6;
    width: 35px;
    position: absolute;
    display: inline-block;
    transform: scale(.5);
    text-align: center;
    border-radius: 50%;
    height: 25px;
    padding-bottom: 10px;
    line-height: .63em;
  }
  .balance{
    display: inline-block;
    color: #dfe;
    margin-top: 2px;
    margin-bottom: 0;
    font-size: 1.3em;
  }
  .numberInput{
    text-align: center;
    font-size: 1.4em;
    margin: 10px;
  }
  .actionButton{
    margin: 4px;
    background: #255;
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
  .history{
    font-size: .7em;
    padding: 3px;
    
  }
  .historyListItem{
    color: #8fc;
    font-size: 1.3em;
  }
  td{
    padding: 1px;
  }
  .tdLeft{
    padding-right: 4px;
    text-align: right;
    width: 35px;
  }
  .tdRight{
    padding-left: 4px;
    text-align: left;
  }
  table{
    width: 50%;
    float: left;
    border-collapse: collapse;
  }
  .pagination{
    display: inline-block;
    margin-left: 50px;
    margin-top: -8px;
    position: absolute;
  }
  .navButton{
    font-size: 30px;
    background: none;
    margin: 0;
    font-weight: 900;
    padding: 0;
    padding-left: 3px;
    padding-right: 3px;
    min-width: initial;
  }
  .enabled{
    color: #0f8;
  }
  disabled{
    color: #666;
  }
  select{
    border: 1px solid #ace4;
    border-radius: 4px;
    background: #0001;
    margin-left: 5px;
    display: inline-block;
    color: #ae1;
  }
  option{
    font-size: 16px;
    color: #ae1;
    background: #223;
    border: none;
  }
  button:disabled{
    color: #111;
  }
  option:disabled{
    color: #666;
  }
  .debit{
    background: #a44a!important;
  }
  .credit{
    background: #4a4a!important;
  }
  .avatarSmall{
    display: inline-block;
    width: 40px!important;
    height: 40px!important;
    border-radius: 50%;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
  }
</style>