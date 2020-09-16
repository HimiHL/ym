<script>
        let token = 'wxtoken:1f62a2cb1b064d6d5f7b2eda0fa5f55a_7bd3388a3a33f186737bdaa7c0ec4341';
        let xxhm = '%7B%22headerImg%22%3A%22http%3A%2F%2Fthirdwx.qlogo.cn%2Fmmopen%2Fic9BcyRDyOIsAe7x0lQW4LnP7vycBzZvFHLsibia5r15vUkV95A3UQSR5wqf5licg0dyU4ibLeNUkbW70v3PPNfXJ2XfaLF7EhBnic%2F132%22%2C%22mobile%22%3A%22181****0102%22%2C%22nickName%22%3A%22%E9%BB%84%E6%B5%B7%E6%9E%97HHL%22%2C%22sex%22%3A2%7D';
        let userInfo = JSON.parse(decodeURIComponent(xxhm));
        console.log('设置了userInfo:', userInfo);
        let openId = 'oCLEa4yAihP3jq4ps2FjzklyotFM';
        console.log('设置了openID:', openId);
        let address = JSON.parse('{"pId":"51","province":"四川省","cId":"5101","city":"成都市","id":"515101","district":""}');
        console.log('设置了地址:', address);
        wx.setStorageSync("cache_login_token", token);
        wx.setStorageSync("cache_user_info", userInfo);
        wx.setStorageSync("cache_open_id", openId);
        wx.setStorageSync('cache_adress', address);
</script>