/*!
 * jQuery Mobile Carousel
 * Source: https://github.com/blackdynamo/jQuery-Mobile-Carousel
 * Demo: http://jsfiddle.net/blackdynamo/yxhzU/
 * Blog: http://developingwithstyle.blogspot.com
 *
 * Copyright 2010, Donnovan Lewis
 * Edits: Benjamin Gleitzman (gleitz@mit.edu)
 * Licensed under the MIT
 */

(function($) {
    $.fn.carousel = function(options) {
        var settings = {
            duration: 300,
            direction: "horizontal",
            minimumDrag: 20,
            startPage: 1,
            startPageID: null,
            beforeStart: function(){},
            afterStart: function(){},
            beforeStop: function(){},
            afterStop: function(){},
            onLoad: function(){}
        };

        $.extend(settings, options || {});

        return this.each(function() {
            if (this.tagName.toLowerCase() != "ul") return;

            var originalList = $(this);
            var pages = originalList.children();
            var mainWidth = originalList.parent().parent().parent().width()-parseFloat(originalList.parent().parent()
                .css('padding-left').replace(/px/,''))*2;
            var width = mainWidth;//originalList.parent().width();


            //console.log(originalList.parent().parent());
            //console.log(originalList.parent().parent().css('padding-left'));
            //console.log(mainWidth);
            var height = originalList.parent().height();
            var navigationBox = $("#"+originalList.attr('id')+'Navigation');
            var isFlip = false;

            //Css
            var containerCss = {position: "relative", overflow: "hidden", width: width, padding: "2px 0 10px"};
            var listCss = {position: "relative", padding: "0", margin: "0", listStyle: "none", width: pages.length * width};
            var listItemCss = {width: width};

            var container = $("<div>").css(containerCss);
            var list = $("<ul>").css(listCss);

            if(settings.startPageID != null) {
                var stopSeg = false;
                $.each(pages, function(i){
                    if($(this).find('div[id ^= ' + settings.startPageID + ']').length > 0 && !stopSeg){
                        settings.startPage = i+1;
                        stopSeg = true;
                    }
                })
            }
            var currentPage = settings.startPage, start, stop;
            if(currentPage != 1){
                list.css({left:(-1*width*(currentPage-1))})
            }
            drawNavigation();
            if (settings.direction.toLowerCase() === "horizontal") {
                list.css({float: "left"});
                $.each(pages, function(i) {
                    var li = $("<li>")
                            .css($.extend(listItemCss, {float: "left"}))
                            .html($(this).html());
                    list.append(li);
                });

                //var startPosition;
                //var endPosition;
                //var startOffset;

                list.bind("swipeleft", function(event){
                    alert('left');
                    //moveLeft();
                    //navigationSwipe();
					//settings.afterStop.apply(list, arguments);
                });

                list.bind("swiperight", function(event){
                    alert('right');
                    //moveRight();
                    //navigationSwipe();
					//settings.afterStop.apply(list, arguments);
                });

                function moveLeft() {
                    if (currentPage == pages.length /*|| dragDelta() < settings.minimumDrag*/) {
                        //list.animate({ left: "+=" + dragDelta()}, settings.duration);
                        return;
                    }
                    var new_width = -1 * width * currentPage;
                    if(os != 'android')
                        list.animate({ left: new_width}, settings.duration);
                    else
                        list.css({left:new_width});
                    currentPage++;
                }

                function moveRight() {
                    if (currentPage == 1 /*|| dragDelta() < settings.minimumDrag*/) {
                        //list.animate({ left: "-=" + dragDelta()}, settings.duration);
                        return;
                    }
                    var new_width = -1 * width * (currentPage - 1);
                    if(os != 'android')
                        list.animate({ left: -1 * width * (currentPage - 2)}, settings.duration);
                    else
                        list.css({left:-1 * width * (currentPage - 2)});
                    currentPage--;
                }

                function navigationSwipe(){
                    navigationBox.find("li").removeClass('active');
                    navigationBox.find("li:eq("+(currentPage-1)+")").addClass('active');
                }

                settings.onLoad.apply(list, arguments);


                /*list.bind('vmouseup', function(event){
                    settings.beforeStop.apply(list, arguments);

                    differ > 0 ? moveLeft() : moveRight();
                    navigationBox.find("li").removeClass('active');
                    navigationBox.find("li:eq("+(currentPage-1)+")").addClass('active');





                    function dragDelta() {
                        return Math.abs(differ);
                    }

                    function adjustment() {
                        return width - dragDelta();
                    }

                    settings.afterStop.apply(list, arguments);
                    isFlip = false;
                });

                list.bind('vmousemove', function(event){
                    if(isFlip) {
                        endPosition = event.pageX;
                        differ = startPosition - endPosition;
                        currentOffset = startOffset - differ;
                        list.css('left', currentOffset);
                        settings.afterStop.apply(list, arguments);
                    }
                })*/




                /*list.draggable({
                    axis: "x",
                    start: function(event) {
                        settings.beforeStart.apply(list, arguments);

                        var data = event.originalEvent.touches ? event.originalEvent.touches[0] : event;
                        start = {
                            coords: [ data.pageX, data.pageY ]
                        };

                        settings.afterStart.apply(list, arguments);
                    },
                    stop: function(event) {
                        settings.beforeStop.apply(list, arguments);

                        var data = event.originalEvent.touches ? event.originalEvent.touches[0] : event;
                        stop = {
                            coords: [ data.pageX, data.pageY ]
                        };

                        start.coords[0] > stop.coords[0] ? moveLeft() : moveRight();
                        navigationBox.find("li").removeClass('active');
                        navigationBox.find("li:eq("+(currentPage-1)+")").addClass('active');

                        function moveLeft() {
                            if (currentPage === pages.length || dragDelta() < settings.minimumDrag) {
                                list.animate({ left: "+=" + dragDelta()}, settings.duration);
                                return;
                            }
                            var new_width = -1 * width * currentPage;
                            list.animate({ left: new_width}, settings.duration);
                            currentPage++;
                        }

                        function moveRight() {
                            if (currentPage === 1 || dragDelta() < settings.minimumDrag) {
                                list.animate({ left: "-=" + dragDelta()}, settings.duration);
                                return;
                            }
                            var new_width = -1 * width * (currentPage - 1);
                            list.animate({ left: -1 * width * (currentPage - 2)}, settings.duration);
                            currentPage--;
                        }

                        function dragDelta() {
                            return Math.abs(start.coords[0] - stop.coords[0]);
                        }

                        function adjustment() {
                            return width - dragDelta();
                        }

                        settings.afterStop.apply(list, arguments);
                    }
                });*/
            } else if (settings.direction.toLowerCase() === "vertical") {
                $.each(pages, function(i) {
                    var li = $("<li>")
                            .css(listItemCss)
                            .html($(this).html());
                    list.append(li);
                });

                list.draggable({
                    axis: "y",
                    start: function(event) {
                        settings.beforeStart.apply(list, arguments);

                        var data = event.originalEvent.touches ? event.originalEvent.touches[0] : event;
                        start = {
                            coords: [ data.pageX, data.pageY ]
                        };

                        settings.afterStart.apply(list, arguments);
                    },
                    stop: function(event) {
                        settings.beforeStop.apply(list, arguments);

                        var data = event.originalEvent.touches ? event.originalEvent.touches[0] : event;
                        stop = {
                            coords: [ data.pageX, data.pageY ]
                        };

                        start.coords[1] > stop.coords[1] ? moveUp() : moveDown();

                        function moveUp() {
                            if (currentPage == pages.length || dragDelta() < settings.minimumDrag) {
                                list.animate({ top: "+=" + dragDelta()}, settings.duration);
                                return;
                            }
                            var new_width = -1 * height * currentPage;
                            list.animate({ top: new_width}, settings.duration);
                            currentPage++;
                        }

                        function moveDown() {
                            if (currentPage == 1 || dragDelta() < settings.minimumDrag) {
                                list.animate({ top: "-=" + dragDelta()}, settings.duration);
                                return;
                            }
                            var new_width = -1 * height * (currentPage - 2);
                            list.animate({ top: new_width}, settings.duration);
                            currentPage--;
                        }

                        function dragDelta() {
                            return Math.abs(start.coords[1] - stop.coords[1]);
                        }

                        function adjustment() {
                            return height - dragDelta();
                        }

                        settings.afterStop.apply(list, arguments);
                    }
                });
            }

            function drawNavigation(){
                if(navigationBox.length > 0){
                    pages.each(function(i){
                        current = false;
                        if(currentPage == i+1)
                            current = true;
                        navigationBox.append("<li "+(current?'class="active"':'')+" rel='"+(i+1)+"'></li>");
                    });
                    navigationBox.find('li').click(function(){
                        var newPage = $(this).attr('rel');
                        var new_width = -1 * width * (newPage-1);
                        list.animate({ left: new_width}, settings.duration);
                        currentPage = newPage;
                        navigationBox.find("li").removeClass('active');
                        navigationBox.find("li:eq("+(currentPage-1)+")").addClass('active');
						settings.afterStop.apply(list, arguments);
                    })

                }
            }

            container.append(list);

            originalList.replaceWith(container);
        });
    };
})(jQuery);