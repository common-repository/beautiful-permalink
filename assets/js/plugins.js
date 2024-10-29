jQuery(document).ready(function(){
    
    if(jQuery("#beautifulproductwc").is(':checked')){
    jQuery("#customlinkproduct").show();  
}else{
    
     jQuery("#customlinkproduct").hide(); 
}

jQuery('#labelbeautifulproductwc').click(function(){
    
    if(jQuery("#beautifulproductwc").is(':checked')){
    
     jQuery("#customlinkproduct").hide();   
}else{
    jQuery("#customlinkproduct").show();
}
    
});







});