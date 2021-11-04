<template>
  <div class="main">
    <div class="displayModal" v-if="state.displayStatus" style="z-index: 100;" ref="statusContainer"></div>
    <div class="displayModal" v-if="state.showDepositModal" style="z-index: 100;" ref="depositModal">
      <div class="depositIframeContainer dark-mode">
        You are depositing: {{state.transactionAmount}}<br><br>
        <div style="margin-bottom: 25px;display: inline-block; background: url(https://jsbot.cantelope.org/uploads/14zkiS.png);width:200px;height:34px;"></div>
         <form id="payment-form">
           <div id="card-container"></div>
           <button id="card-button">complete deposit</button>
           <button @click="state.showDepositDialog = false">cancel</button>
         </form>
         <div id="payment-status-container"></div>
      </div>
    </div>
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
        <button @click="completeTransaction()" :class="{'disabledbg': !state.transactionAmount || !(+this.toCents(this.state.transactionAmount))}">do it</button>
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
//require('../assets/css/style.css')
require('../assets/js/square.js')
require('../assets/css/darkmode.css')
require('../assets/js/sq-card-pay.js')
require('../assets/css/sq-payment.css')
//require('../assets/js/sq-payment-flow.js')

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
    toCents(str){
      return this.toFloat(str)*100
    },
    toFloat(str){
      return Number(str.replace(/[^0-9.-]+/g,""));
    },
    doTransaction(){
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
    completeTransaction(){
      if(this.curTransType=='deposit'){
        if(+this.toCents(this.state.transactionAmount)){
          this.state.showDepositModal = true
          this.$nextTick(()=>this.deployDeposit())
        }
      } else {
        this.doTransaction()
      }
    },
    balanceString(){
      return '<span style="padding-left: 10px; padding-right: 10px; color: ' + (this.state.userBalance < 0 ? '#f88' : '#8fc') + ';background: ' + (this.state.userBalance < 0 ? '#411' : '#142') + '">' + this.formatter.format(this.state.userBalance/100) + '</span>'
    },
    deployDeposit(){
      const darkModeCardStyle = {
        '.input-container': {
          borderColor: '#2D2D2D',
          borderRadius: '6px',
        },
        '.input-container.is-focus': {
          borderColor: '#006AFF',
        },
        '.input-container.is-error': {
          borderColor: '#ff1600',
        },
        '.message-text': {
          color: '#999999',
        },
        '.message-icon': {
          color: '#999999',
        },
        '.message-text.is-error': {
          color: '#ff1600',
        },
        '.message-icon.is-error': {
          color: '#ff1600',
        },
        input: {
          backgroundColor: '#2D2D2D',
          color: '#FFFFFF',
          fontFamily: 'helvetica neue, sans-serif',
        },
        'input::placeholder': {
          color: '#999999',
        },
        'input.is-error': {
          color: '#ff1600',
        },
        '@media screen and (max-width: 600px)': {
           'input': {
              'fontSize': '12px',
           }
        }     
      };
      window.applicationId = 'sq0idp-9VjbEv-sv-rMbsxTLRB5bQ'
      window.locationId = 'LHXJHV64CEVDM'
      window.currency = 'USD'
      window.country = 'US'

      const appId = 'sq0idp-9VjbEv-sv-rMbsxTLRB5bQ';
      const locationId = 'LHXJHV64CEVDM';
      async function initializeCard(payments) {
         const card = await payments.card({
          style: darkModeCardStyle,
         });
         await card.attach('#card-container'); 
         return card; 
       }

       //document.addEventListener('DOMContentLoaded', async function () {
       async function go() {
        if (!window.Square) {
          throw new Error('Square.js failed to load properly');
        }

        const payments = window.Square.payments(appId, locationId);
        let card;
        try {
          card = await initializeCard(payments);
        } catch (e) {
          console.error('Initializing Card failed', e);
          return;
        }

        async function handlePaymentMethodSubmission(event, paymentMethod) {
          event.preventDefault();
          cardButton.disabled = true;
          const token = await tokenize(paymentMethod);
          const paymentResults = await createPayment(token);
        }

        const cardButton = document.getElementById(
          'card-button'
        );
        cardButton.addEventListener('click', async function (event) {
          await handlePaymentMethodSubmission(event, card);
        });
      }

      let cents = this.toCents(this.state.transactionAmount)
      let url = this.state.baseURL + '/deposit/process-payment.php'
      async function createPayment(token) {
         let statusContainer = document.getElementById(
           'payment-status-container'
         );
         statusContainer.style.visibility = 'hidden';
         const body = JSON.stringify({
           locationId,
           sourceId: token,
           //token: 'EAAAEAvNMiy0v19NPeEisojLnTiFxdcvZvUSOm2z9MKQGJiIiimIMPQQpsnDo0We',
           amount: cents
         });
         const paymentResponse = await fetch(url, {
           method: 'POST',
           headers: {
             'Content-Type': 'application/json',
           },
           body,
         }).then(text=>text.json()).then(data=>{
            console.log(data)
            if(data.payment.card_details.status=='CAPTURED'){
              displayPaymentResults('SUCCESS');
            } else {
              displayPaymentResults('FAILURE');
            }
            //console.log('Payment Success', paymentResults);
         })
         //const errorBody = await paymentResponse.text();
         //throw new Error(errorBody);
       }

       async function tokenize(paymentMethod) {
         const tokenResult = await paymentMethod.tokenize();
         if (tokenResult.status === 'OK') {
           return tokenResult.token;
         } else {
           let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
           if (tokenResult.errors) {
             errorMessage += ` and errors: ${JSON.stringify(
               tokenResult.errors
             )}`;
           }
           throw new Error(errorMessage);
         }
       }

       // Helper method for displaying the Payment Status on the screen.
       // status is either SUCCESS or FAILURE;
       let self=this
       function displayPaymentResults(status) {
         let statusContainer = document.getElementById(
           'payment-status-container'
         );
         statusContainer.style.visibility = 'visible';
         if (status === 'SUCCESS') {
           statusContainer.classList.remove('is-failure');
           statusContainer.classList.add('is-success');
           setTimeout(()=>{
             self.state.showDepositModal=false
             self.doTransaction()
           },1000)
         } else {
           statusContainer.classList.remove('is-success');
           statusContainer.classList.add('is-failure');
         }
       }
       go()
    }
  },
  mounted(){
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
  .disabled{
    color: #666;
  }
  .disabledbg{
    background: #666;
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
  .depositIframeContainer{
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 50%;
    min-width: 500px;
    padding: 0px;
    height: 220px;
    font-size: 20px;
    text-align: left;
    color: #ace;
    background: #206;
    border-radius: 3px;
    box-shadow: 0px 0px 80px 100px #206;
  }
  .depositIframe{
    width: 100%;
    height: 100%;
    border: none;
    background: none;
  }
  #payment-status-container {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-sizing: border-box;
    border-radius: 50px;
    margin: 0 auto;
    width: 225px;
    height: 48px;
    visibility: hidden;
  }

  #payment-status-container.missing-credentials {
    width: 350px;
  }

  #payment-status-container.is-success:before {
    content: '';
    background-color: #00b23b;
    width: 16px;
    height: 16px;
    margin-right: 16px;
    -webkit-mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM11.7071 6.70711C12.0968 6.31744 12.0978 5.68597 11.7093 5.29509C11.3208 4.90422 10.6894 4.90128 10.2973 5.28852L11 6C10.2973 5.28852 10.2973 5.28853 10.2973 5.28856L10.2971 5.28866L10.2967 5.28908L10.2951 5.29071L10.2886 5.29714L10.2632 5.32224L10.166 5.41826L9.81199 5.76861C9.51475 6.06294 9.10795 6.46627 8.66977 6.90213C8.11075 7.4582 7.49643 8.07141 6.99329 8.57908L5.70711 7.29289C5.31658 6.90237 4.68342 6.90237 4.29289 7.29289C3.90237 7.68342 3.90237 8.31658 4.29289 8.70711L6.29289 10.7071C6.68342 11.0976 7.31658 11.0976 7.70711 10.7071L11.7071 6.70711Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E");
    mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM11.7071 6.70711C12.0968 6.31744 12.0978 5.68597 11.7093 5.29509C11.3208 4.90422 10.6894 4.90128 10.2973 5.28852L11 6C10.2973 5.28852 10.2973 5.28853 10.2973 5.28856L10.2971 5.28866L10.2967 5.28908L10.2951 5.29071L10.2886 5.29714L10.2632 5.32224L10.166 5.41826L9.81199 5.76861C9.51475 6.06294 9.10795 6.46627 8.66977 6.90213C8.11075 7.4582 7.49643 8.07141 6.99329 8.57908L5.70711 7.29289C5.31658 6.90237 4.68342 6.90237 4.29289 7.29289C3.90237 7.68342 3.90237 8.31658 4.29289 8.70711L6.29289 10.7071C6.68342 11.0976 7.31658 11.0976 7.70711 10.7071L11.7071 6.70711Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E");
  }

  #payment-status-container.is-success:after {
    content: 'Payment successful';
    font-size: 14px;
    line-height: 16px;
  }

  #payment-status-container.is-failure:before {
    content: '';
    background-color: #cc0023;
    width: 16px;
    height: 16px;
    margin-right: 16px;
    -webkit-mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A");
    mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A");
  }

  #payment-status-container.is-failure:after {
    content: 'Payment failed';
    font-size: 14px;
    line-height: 16px;
  }

  #payment-status-container.missing-credentials:before {
    content: '';
    background-color: #cc0023;
    width: 16px;
    height: 16px;
    margin-right: 16px;
    -webkit-mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A");
    mask: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM5.70711 4.29289C5.31658 3.90237 4.68342 3.90237 4.29289 4.29289C3.90237 4.68342 3.90237 5.31658 4.29289 5.70711L6.58579 8L4.29289 10.2929C3.90237 10.6834 3.90237 11.3166 4.29289 11.7071C4.68342 12.0976 5.31658 12.0976 5.70711 11.7071L8 9.41421L10.2929 11.7071C10.6834 12.0976 11.3166 12.0976 11.7071 11.7071C12.0976 11.3166 12.0976 10.6834 11.7071 10.2929L9.41421 8L11.7071 5.70711C12.0976 5.31658 12.0976 4.68342 11.7071 4.29289C11.3166 3.90237 10.6834 3.90237 10.2929 4.29289L8 6.58579L5.70711 4.29289Z' fill='black' fill-opacity='0.9'/%3E%3C/svg%3E%0A");
  }

  #payment-status-container.missing-credentials:after {
    content: 'applicationId and/or locationId is incorrect';
    font-size: 14px;
    line-height: 16px;
  }
</style>