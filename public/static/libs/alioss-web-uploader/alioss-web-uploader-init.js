
var class_name = document.getElementsByClassName("ali_webupload_pick");

for (let index = 0; index < class_name.length; index++) {
    const element = class_name[index];
    let dom_id = element.id;
    if(dom_id.indexOf("ali_upload_btn")>-1){
        element.addEventListener("click",function(e){
            document.querySelector("#"+document.querySelector("#"+dom_id).getAttribute("for")).click();
        });

    }
    if(dom_id.indexOf("ali_upload_input")>-1){
        element.addEventListener('change', function (e) {
            let uploadFile = e.target.files[0];
            var filename = e.target.value;
            filename = filename.split(/[\/\\]/).pop();
            filename =Date.now() +filename ;
            ali_upload(filename,uploadFile,dom_id);
        });   
    }


}


    function ali_upload(filename,uploadFile,dom_id){

        var client = new AliOSSWebUploader({
            region: 'oss-cn-shanghai',
            bucket: 'video-snot-12220',
            accessKeyId: 'LTAI5tPpFVhueervntEWfWDy',
            accessKeySecret: 'ORKTsDyugCzcA7PbHOElg9Mw0bM4ah',
            endpoint: 'video-snot-12220.oss-cn-shanghai.aliyuncs.com',
            secure: true,
        });
        client.postObject(filename, uploadFile,  {
            'x-oss-object-acl': 'public-read',
            'success_action_status': 200,
            timeout: 300000,
            onProgress: function (e) {
                console.log('complete', e.percent.toFixed(2), '%');
                let a = dom_id.split("_");
                let n_dom_id = "file_list_"+ a[a.length-1]
                let progress = document.querySelector("#"+n_dom_id).querySelector(".progress");
                if(progress){
                    progress.style.display = "block";
                }
                let progress_bar = document.querySelector("#"+n_dom_id).querySelector(".progress-bar");
                if(progress_bar){
                    progress_bar.style.width = parseInt(e.percent.toFixed(2))+'%';
                }
            },
            onSuccess: function(){
                var url = client.generateObjectUrl(filename);
                console.log('upload success',url);
                let dom_val =  document.querySelector("#"+dom_id.replaceAll('input','value'));
                let dom_src =  document.querySelector("#"+dom_id.replaceAll('input','src'));
                dom_val.value = url;
                dom_src.src = url;                
            },
            onError: function(e){
                alert(e.message || "Unknow error, maybe some value is wrong.");
                console.warn(e);
            }
        });

    }

    
