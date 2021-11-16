<template>
  <div class="header">
    <div class="header-logo"></div>
    <div class="loginContainer">
      <div v-if="state.loggedin">
        welcome, <span class="loggedinusername" v-html="state.loggedinUserName"></span><br>
        <img @click="state.userSettingsVisible=!state.userSettingsVisible" class="settingsIcon" src="https://jsbot.cantelope.org/uploads/1vfDF7.png">
        <div @click="state.userSettingsVisible=!state.userSettingsVisible" class="avatar" :style="'background-image:url('+state.userAvatar+')'"></div>
        <button v-if="state.loggedin" class="logoutButton" @click="state.logout()" title="log out of payway">log out</button>
      </div>
      <div v-else>
        <button class="loginButton" @click="state.showLoginPrompt()">log in / register</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Header',
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
  .header{
    height: 80px;
    background: linear-gradient(50deg,#f182,#5066,#5076,#5166,#4286,#2384,#0334,#0224,#0222,#0000);
    overflow: hidden;
    border-bottom: 1px solid #40fa;
    position: fixed;
    z-index: 5;
    width: 100%;
    top: 0;
    left: 0;
  }
  .header-logo{
    width: 210px;
    position: relative;
    height: 80px;
    background-image: url(https://jsbot.cantelope.org/uploads/GWQ3b.png);
    background-repeat: no-repeat;
    background-position: -15px center;
    background-size: 100% 100%;
  }
  .loggedinusername{
    font-weight: 900;
    font-size: 1.1em;
    color: #fff;
  }
  .logoutButton{
    min-width: initial;
    max-width: 70px;
    width: 70px;
    padding: 2px;
    padding-bottom: 4px;
    margin-right: 20px;
    background: #266;
    color: #4ff;
    text-shadow: 2px 2px 2px #000;
    font-size: 16px;
    font-weight: 400;
    float: right;
  }
  .loginContainer{
    position: absolute;
    left: 200px;
    top: 0;
    width: calc(100% - 270px);
    min-width: 200px;
    text-align: right;
    padding-right: 60px;
    padding-top: 5px;
    height: 68px;
  }
  .settingsIcon{
    width: 60px;
    cursor: pointer;
    position: absolute;
    right: 0;
    top: 0;
    margin-top: 7px;
    margin-right: 0;
  }
  .avatar{
    width: 50px;
    height: 50px;
    position: relative;
    float:right;
    background-position: center;
    background-size: cover;
    border-radius: 50%;
    cursor: pointer;
  }
</style>
