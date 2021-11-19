<template>
  <Header :state="state"/>
  <UserSettings v-if="state.userSettingsVisible" :state="state"/>
  <LoginForm v-if="state.loginPromptVisible" :state="state"/>
  <Main :state="state"/>
  <Footer :state="state"/>
</template>

<script>
import Header from './components/Header.vue'
import Main from './components/Main.vue'
import Footer from './components/Footer.vue'
import LoginForm from './components/LoginForm.vue'
import UserSettings from './components/UserSettings.vue'

export default {
  name: 'App',
  components: {
    Header, Main, Footer, LoginForm, UserSettings
  },
  data(){
    return {
      state:{
        featuredCurrencies:[],
        history: [],
        showLoginPrompt: null,
        userAgent: null,
        transactionAmount: '',
        withdrawalAmount: '',
        graphRange: 'DAY',
        graphRangeUnits: '1',
        sendAmount: '',
        pages: 0,
        globalTotal: 0,
        globalAssets: [],
        isAdmin: false,
        requestAmount: '',
        userBalance: 0,
        userHistory: [],
        historyPage: 0,
        transactionPartner: '',
        getBalance: null,
        closePrompts: null,
        errorGettingBalance: false,
        setCookie: null,
        displayTransactionDialog: false,
        displayStatus: false,
        getAvatar: null,
        defaultAvatar: 'https://jsbot.cantelope.org/uploads/1pnBdc.png',
        userAvatar: '',
        loginPromptVisible: false,
        showDepositModal: false,
        login: null,
        logout: null,
        loggedinUserID: null,
        username: '',
        transactionsPerPage: 'default',
        password: '',
        regusername: '',
        regpassword: '',
        showRegister: false,
        showUserSettings: null,
        userSettingsVisible: false,
        baseURL: 'https://payway.dweet.net',
        loggedin: false,
        rootDomain: window.location.hostname,
        userName: '',
        passhash: '',
      }
    }
  },
  methods:{
    getAvatar(id){
      return this.state.userAvatar ? this.state.userAvatar : this.state.defaultAvatar
    },
    setCookie(){
      let cookies = document.cookie
      cookies.split(';').map(v=>{
        if(v.indexOf('autoplay')==-1){
          document.cookie = v + '; expires=' + (new Date(0)).toUTCString() + '; path=/; domain=' + this.state.rootDomain
        }
      })
      document.cookie = 'loggedinuser=' + this.state.loggedinUserName + '; expires=' + (new Date((Date.now()+3153600000000))).toUTCString() + '; path=/; domain=' + this.state.rootDomain
      document.cookie = 'loggedinuserID=' + this.state.loggedinUserID + '; expires=' + (new Date((Date.now()+3153600000000))).toUTCString() + '; path=/; domain=' + this.state.rootDomain
      document.cookie = 'token=' + this.state.passhash + '; expires=' + (new Date((Date.now()+3153600000000))).toUTCString() + '; path=/; domain=' + this.state.rootDomain
    },
    decToAlpha(n){
      let alphabet='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
      let ret='', r
      while(n){
        ret = alphabet[Math.round((n/62-(r=n/62|0))*62)|0] + ret
        n=r
      }
      return ret == '' ? '0' : ret
    },
    confirmClose(){
      this.state.logout()
    },
    showUserSettings(){
      document.getElementsByTagName('HTML')[0].style.overflowY = 'hidden'
      this.state.userSettingsVisible = true
    },
    showLoginPrompt(){
      document.getElementsByTagName('HTML')[0].style.overflowY = 'hidden'
      this.state.username = this.state.password = this.state.regusername = this.state.regpassword = ''
      this.state.invalidLoginAttempt = false
      this.state.loginPromptVisible = true
    },
    toggleLogin(){
      this.state.loggedin = !this.state.loggedin
    },
    checkLoggedIn(){
      let cookies = document.cookie
      let l = (document.cookie).split(';').filter(v=>v.split('=')[0].trim()==='token')
      if(l.length) {
        this.state.passhash = l[0].split('=')[1]
        l = (document.cookie).split(';').filter(v=>v.split('=')[0].trim()==='loggedinuser')
        if(l.length){
          this.state.userName = l[0].split('=')[1]
        }
      }
      let sendData = {userName: this.state.userName, passhash: this.state.passhash}
      fetch(this.state.baseURL + '/checkEnabled.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
        if(data[0]){
          this.state.loggedin = true
          this.state.loggedinUserName = data[1]
          this.state.loggedinUserID = data[2]
          this.state.passhash = data[3]
          this.state.userAvatar = data[4] ? data[4] : this.state.defaultAvatar
          this.state.transactionsPerPage = +data[5]
          this.state.isAdmin = !!(+data[6])
          this.setCookie()
          this.state.loginPromptVisible = false
          this.state.getBalance()
        }
      })
    },
    getBalance(){
      let sendData = {
        userName: this.state.userName,
        passhash: this.state.passhash,
        historyPage: this.state.historyPage,
        graphRange: this.state.graphRange,
        graphRangeUnits: this.state.graphRangeUnits
      }
      fetch(this.state.baseURL + '/getBalance.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
        if(data[0]){
          this.state.userBalance = data[1]
          this.state.userHistory = data[2]
          this.state.pages = data[3]
          this.state.globalTotal = data[4]
          this.state.globalAssets = data[5]
          this.state.globalAssets.map(v=>{
            v.featured = !!(+v.featured)
          })
          this.state.globalAssets.sort((a,b)=>b.featured - a.featured)
          this.state.history = data[6]
          this.state.featuredCurrencies = data[7]
          if(this.state.historyPage > this.state.pages - 1){
            this.state.historyPage = this.state.pages -1
          }
        }else{
          this.state.errorGettingBalance = true
        }
      })
    },
    login(){
      let sendData = {userName: this.state.userName, password: this.state.password}
      fetch(this.state.baseURL + '/login.php',{
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sendData),
      })
      .then(json=>json.json()).then(data=>{
        if(data[0]){
          //this.state.loggedin = true
          this.state.loggedinUserName = data[1]
          this.state.loggedinUserID = data[2]
          this.state.passhash = data[3]
          this.state.userAvatar = data[4] ? data[4] : this.state.defaultAvatar
          this.state.transactionsPerPage = +data[5]
      this.state.isAdmin = !!data[6]
          this.state.getBalance()
          this.state.loginPromptVisible = false
          this.setCookie()
          setTimeout(()=>window.location.reload(),100)
        }else{
          this.state.invalidLoginAttempt = true
        }
      })
    },
    closePrompts(){
      this.state.loginPromptVisible = false
      this.state.userSettingsVisible = false
      this.state.showRegister = false
      this.state.displayLoginRequired = false
      document.getElementsByTagName('HTML')[0].style.overflowY = 'visible'
    },
    logout(){
      let cookies = document.cookie
      cookies.split(';').map(v=>{
        if(v.indexOf('autoplay')==-1){
          document.cookie = v + '; expires=' + (new Date(0)).toUTCString() + '; path=/; domain=' + this.state.rootDomain
        }
      })
      this.state.loggedin = false
      this.state.isAdmin = false
      this.state.loggedinUserID = this.state.loggedinUserName = ''
      setTimeout(()=>window.location.reload(), 10)
    }
  },
  computed:{
    userAvatar(){
      return this.state.userAvatar || this.state.defaultAvatar
    }
  },
  mounted(){
    this.state.closePrompts = this.closePrompts
    this.state.setCookie = this.setCookie
    this.state.login = this.login
    this.state.loggedinUserID = this.loggedinUserID
    this.state.getBalance = this.getBalance
    this.state.getAvatar = this.getAvatar
    this.state.userAvatar = this.userAvatar
    this.state.logout = this.logout
    this.state.showLoginPrompt = this.showLoginPrompt
    let l = (document.cookie).split(';').filter(v=>v.split('=')[0].trim()==='token')
    if(l.length) this.checkLoggedIn()
    setInterval(()=>this.state.getBalance(), 10000)
    this.state.getBalance()
  }
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Play&display=swap');
#app{
  min-width: 475px;
}
html{
  margin: 0;
  font-family: Play;
  color: #8fc;
  min-height: 100%;
  min-width: 475px;
  background: #000;
}
body{
  scroll-behavior: smooth;
  overflow-y: scroll;
  margin: 0;
  background: linear-gradient(45deg, #011, #002, #200);
  font-family: Play;
  color: #8fc;
  min-width: 475px;
  overflow-x: hidden;
  min-height: calc(100vh - 80px);
}
.input{
  overflow-X: hidden;
  text-align: center;
  font-size: 24px;
  background: #0004;
  border: none;
  border-bottom: 2px solid #2fa;
  width: 300px;
  color: #ffa;
}
input[type=text]{
  font-size: 24px;
  background: #0004;
  border: none;
  border-bottom: 2px solid #2fa;
  width: 300px;
  color: #ffa;
}
input[type=checkbox]{
  cursor: pointer;
  transform: scale(1.5);
  margin: 8px;
  margin-left: 5px;
}
input:focus{
  outline: none;
}
button:focus{
  outline: none;
}
select:focus{
  outline: none;
}
button{
  font-family: Play;
  font-size: 18px;
  border: none;
  border-radius: 3px;
  background: #adc;
  padding: 4px;
  padding-bottom: 6px;
  padding-left: 10px;
  padding-right: 10px;
  font-weight: 900;
  margin: 10px;
  min-width: 140px;
  cursor: pointer;
}
.hideOverflow{
  overflow: hidden;
}
/* width */
option{
  text-align: center;
}
select{
  background: #012;
  color: #ff0;
}
::-webkit-scrollbar {
  width: 12px!important;
}
::-webkit-scrollbar:hover{
  cursor: pointer!important;
}

::-webkit-scrollbar-track {
  background: #133!important; 
}
::-webkit-scrollbar-track:hover {
  cursor: pointer!important;
}
 
::-webkit-scrollbar-thumb {
  background: #166!important;
}

::-webkit-scrollbar-thumb:hover {
  background: #588; 
  cursor: pointer!important;
}
a,button{
  cursor: pointer;
}
a{
  color: #fa0;
  text-decoration: none;
}
.spacerDiv{
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #0000, #4dd8, #0000);
  margin-top: .3em;
  margin-bottom: .3em;
}
/* Customize the label (the checkboxLabel) */
.checkboxLabel {
  display: inline-block;
  position: relative;
  padding-left: 18px;
  margin-bottom: -2px;
  margin-top: 3px;
  cursor: pointer;
  font-size: 22px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the browser's default checkbox */
.checkboxLabel input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

/* Create a custom checkbox */
.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 25px;
  width: 25px;
  border: 1px solid #4688;
  background-color: #123;
}

/* On mouse-over, add a grey background color */
.checkboxLabel:hover input ~ .checkmark {
  background-color: #234;
}

/* When the checkbox is checked, add a blue background */
.checkboxLabel input:checked ~ .checkmark {
  background-color: #086;
}

/* Create the checkmark/indicator (hidden when not checked) */
.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the checkmark when checked */
.checkboxLabel input:checked ~ .checkmark:after {
  display: block;
}

/* Style the checkmark/indicator */
.checkboxLabel .checkmark:after {
  left: 4px;
  top: 0;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 3px 3px 0;
  -webkit-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}
video:focus{
  outline: none;
}
.loginButton{
  position: fixed;
  top: 12px;
  right: 15px;
  z-index: 2000;
  color: #8fc;
  text-shadow: 2px 2px 2px #000;
  background: #199f;
}
</style>
