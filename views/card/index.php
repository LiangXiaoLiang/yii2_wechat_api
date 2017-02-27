
<div class="site-index">

    <div class="jumbotron">
        <h2>卡券测试</h2>
        <p></p>
        <p class="lead"></p>


        <button id="addCard" class="btn btn-lg btn-success">领取卡券</button>
    </div>

    <div class="body-content">


</div>
<script src="https://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
  wx.config({
      debug: false,
      appId: '<?= $appId?>',
      timestamp: '<?= $timestamp?>',
      nonceStr: '<?= $nonceStr?>',
      signature: '<?=$signature?>',
      jsApiList: [
        'checkJsApi',
        'onMenuShareTimeline',
        'onMenuShareAppMessage',
        'onMenuShareQQ',
        'onMenuShareWeibo',
        'onMenuShareQZone',
        'hideMenuItems',
        'showMenuItems',
        'hideAllNonBaseMenuItem',
        'showAllNonBaseMenuItem',
        'translateVoice',
        'startRecord',
        'stopRecord',
        'onVoiceRecordEnd',
        'playVoice',
        'onVoicePlayEnd',
        'pauseVoice',
        'stopVoice',
        'uploadVoice',
        'downloadVoice',
        'chooseImage',
        'previewImage',
        'uploadImage',
        'downloadImage',
        'getNetworkType',
        'openLocation',
        'getLocation',
        'hideOptionMenu',
        'showOptionMenu',
        'closeWindow',
        'scanQRCode',
        'chooseWXPay',
        'openProductSpecificView',
        'addCard',
        'chooseCard',
        'openCard'
      ]
  });
    wx.ready(function () {
  document.querySelector('#addCard').onclick = function () {
    wx.addCard({
      cardList: [
        {
          cardId: '<?=$card_id?>',
          cardExt: '<?=$cardExt?>'
        },
      ],
      success: function (res) {
        alert('已添加卡券：' + JSON.stringify(res.cardList));
      },
      cancel: function (res) {
        alert(JSON.stringify(res))
      }
    });
  };
  });

</script>    
