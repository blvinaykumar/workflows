define(['jquery'], function($) {
    return {
        init: function() {
           
             $(document).ready(function () {
                
                $('.blfull_pdf').on('click', function (e) {
                    let elem = document.querySelector('#content-wrapper');
                    if (!document.fullscreenElement) {
                        elem.requestFullscreen().catch(err => {
                            alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
                        });
                    } else {
                        document.exitFullscreen();
                    }

                })
             });
        }
    }
});