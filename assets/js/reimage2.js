const $ = require('jquery');
require("./initialize");
function attachEventToElement(pickerDivEl){
    var loadingMethodExist = (typeof setLoading == "function"&& typeof finishLoading == "function");

    var width = $(pickerDivEl).attr("data-width");
    var height = $(pickerDivEl).attr("data-height");
    var data = {"width" : width, "height" : height};

    $(pickerDivEl).find('span').click(function(){
        $(this).parent().trigger('click');
    });

    $(pickerDivEl).dropzone({
        url: Routing.generate("re_image2_download"),
        params: data,
        success: function(a, b){
            var result = JSON.parse(b);
            if(result.hasOwnProperty("filepath") && result.hasOwnProperty("assetpath")){
                $(pickerDivEl).css("z-index: 1; position: relative;");

                var newElement = $('<img class="btnPickREImage2" src="'+result.assetpath+'" data-width="'+width+'" data-height="'+height+'" style="max-width: 100%; position: absolute; top:0; left: 0; z-index: 2; opacity: 0;" />');
                $(pickerDivEl).closest('.reImageHolder').find('input').val(result.filepath);
                $(pickerDivEl).closest('.reImageHolder').find('input').trigger('change');
                $(pickerDivEl).after(newElement);
                $(newElement).width($(pickerDivEl).width());
                $(newElement).height($(pickerDivEl).height());
                $(newElement).fadeTo(1000, 1, function(){
                    $(pickerDivEl).remove();
                    $(newElement).css("position", "static");
                    $(newElement).css("width", "auto");
                    $(newElement).css("height", "auto");
                    attachEventToElement(newElement);
                });
            }
            else if(result.hasOwnProperty("error")){
                console.log(result.error);
            }
            else{
                console.log(b);
            }

            if(loadingMethodExist){
                finishLoading($(pickerDivEl))
            }
        },
        error: function(a, b){
            if(loadingMethodExist){
                finishLoading($(pickerDivEl))
            }
        },
        accept: function(file, done) {
            // console.log(file.size);
            if(file.size > 10*1024*1024){
                alert("Le fichier que vous essayez d'envoyer doit faire moins de 10mo");
                return;
            }
            else if ((/\.(jpg|jpeg|png|gif)$/i).test(file.name)){
                if(loadingMethodExist){
                    setLoading($(pickerDivEl))
                }
                done();
            }
            else { 
                alert("Le fichier doit être au format jpg ou png");
                return false;
             }
        },
        clickable: true
    });
}

$(document).ready(function(){


    $.initialize(".btnPickREImage2", function(){
        attachEventToElement($(this));
    });
    $('div.btnPickREImage2').each(function(){
        var width = parseInt($(this).width());
        var dataWidth = parseInt($(this).attr("data-width"));
        var dataHeight = parseInt($(this).attr("data-height"));
        var newHeight = dataHeight * width / dataWidth;
        if(newHeight < parseInt($(this).height())){
            $(this).height(dataHeight * width / dataWidth);
        }

    });
});