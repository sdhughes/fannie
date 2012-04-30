<?php
header("Content-type: text/css");
$white = '#fff';
$dkgray = '#333';
$highlight = '#aefbc7';
$hover = '#fff';
?>
/*****
 *  
 *
 *****/
#new_subdept_id {
    width: 4em;
}
#new_subdept_name {

}
input#subdept_id {

    width: 4em;
}
#edit_inputs {
   margin: 0 2em;
}
.sd_col {
    display: inline-block;

    vertical-align: top;
}
#editPane {
    border: 1px solid black;
    text-align: center;
    position: absolute;
    display: none;
}
#editPane > div > div {
    display: inline-block;

}
.myButton {
    
    border: 1px solid black;
    border-radius: .2em;
    background: #eee;

    margin: 0 1em;
    padding: .3em .5em;
    font-size: .7em;

    
}   
.myButton:hover {
    background-color: gray;
    color: white;
    cursor: pointer;
}
