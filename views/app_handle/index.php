<?php

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <div class="jumbotron">
        <h2>宏达微云</h2>
        <p></p>
        <p class="lead"></p>

        <p><a id='bindapi' class="btn btn-lg btn-success" href="#">绑定公众号</a></p>
    </div>

    <div class="body-content">


</div>
        <script type="text/javascript">
       /**
     * 异步调取2级指标下的对应3级子指标。
     * */

   $("#bindapi").click(function(){ 
                        $.ajax({
                                        type: "POST",
                                        url: "",
                                        data:{ name:'humanluo',age:25 },
                                        success: function(result){ 
                                                    console.log('humanluo');
                                        }
                                     });
   });
    </script>
