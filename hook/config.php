<?php
    $HOOK_CONF=array(
        'passage'=>array(
            'instal' =>function(){},
            'save'   =>function(){},
            'getsave'=>function(){},
            'delsave'=>function(){},
            'read'   =>function(){},
            'send'   =>function(){},
            'editTxt'=>function(){},
            'editScr'=>function(){},
            'hide'   =>function(){}
        ),
        'user'=>array(
            'instal'        =>function(){},
            'sendCoin'      =>function(){},
            'costUserCoin'  =>function(){},
            'setDescription'=>function(){},
            'getuser'       =>function(){},
            'login'         =>function(){},
            'changepwd'     =>function(){},
            'register'      =>function(){}
        ),
        'comm'=>array(
            'get'     =>function(){},
            'del'     =>function(){},
            'send'    =>function(){},
            'sendCoin'=>function(){}
        )
    );
?>