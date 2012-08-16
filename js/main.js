    function sw(url) {
        x = 900;
        y = 550;
        cx = screen.width/2 - x/2;
        cy = screen.height/2 - y/2;
        window.open(
                        url,
                        '',
                        "toolbar=no, status=no, directories=no, menubar=no, resizable=no, width="+x+", height="+y+", scrollbars=yes, top="+cy+", left="+cx
                    );
    }