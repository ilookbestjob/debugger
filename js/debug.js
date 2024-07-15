
let constsetup


$(async function () {


    $("#jurnal").click(function () {
        $(".debuginfocontainer").hide();
        $(".journalinfocontainer").css({ display: "grid" });
        $(".journalinfocontainer").show();
        $(this).addClass("selected")
        $("#debuginfo").removeClass("selected")
        buildsessions(".sessions")
    })


    $("#debuginfo").click(function () {
        $(".debuginfocontainer").show();
        $(".journalinfocontainer").hide();
        $(this).addClass("selected")
        $("#jurnal").removeClass("selected")
    })


    $(".infocontainer").find(".header").click(function () {

        $(this).parent().find(".data").slideToggle("fast", function () {
            //$(this).css("display")=="none"?
            console.log(this)
            $(this).parent().find(".arrow").html($(this).css("display") == "none" ? "&#9660;" : "&#9650;")
            //css("display")=="none"?
        });

    })



    buildSetup()
    constsetup = bildSetupItem("сопоставление с Nordcom.Const")
    buildconstcontent(constsetup)

    $("#setup").click(function () {

        $(".setup").css({ display: "grid" })

    })


    $(".setup__footer").click(function () {
        $(".setup").css({ display: "none" })


    })
    /*
        $(".jsonformat").find("div").click(
    
            function () {
    
    console.log(this)
    
                $(this).find("div").toggle()
    
    
    
    
            }
    
        )*/

})


function tdt(e) {

    console.log("this", e)

    $(e).siblings(".node").each(function () {


        $(this).toggle('fast', function () {
            
            console.log("thisss",this)
            let tittle=$(this).siblings(".nodetittle").html()
            tittle=tittle.split(" ");
            if (tittle.length>1){
            tittle.pop();
            tittle=tittle.join(" ")}
            $(this).siblings(".nodetittle").html(tittle+" "+($(this).css("display")!="none"?"&#9650":"&#9660"))

            if ($(this).css("display")!="none"){
                $(this).siblings(".nodetittle").css()

            }


         })
    })

}


const getsessions = async () => {
    let result = await fetch(window.location.href.split("?")[0] + "?a=0&k=lK_wh__rFg2&deb&isdeb&debugaction=getsessions")
    return result.json();


}

const buildsessions = async (target) => {


    let sessions = await getsessions()
    console.log(target, sessions)
    $(target).html("");
    sessions.forEach(session => {
        let dateParts = session.date.split("-");
        let jsdate = dateParts[2].substr(0, 2) + "." + dateParts[1] + "." + dateParts[0] + " " + dateParts[2].substr(3);
        $(target).append('<div class="session" session="' + session.session + '"><div class="session_date"> ' + jsdate + '</div><div class="session_count">' + session.count + '</div></div>');

    });



    $(".session").click(function () {
        $(".session").removeClass("selected")
        $(this).addClass("selected")
        buildsessiondata(".sessionsdata", $(this).attr("session"))
    })
    buildsessiondata(".sessionsdata", sessions.length >= 1 ? sessions[0].session : 0)
}



const getsessiondata = async (session) => {
    let result = await fetch(window.location.href.split("?")[0] + "?a=0&k=lK_wh__rFg2&deb&isdeb&debugaction=getlogs&debugsession=" + session)
    return result.json();


}

const buildsessiondata = async (target, session) => {


    let sessiondata = await getsessiondata(session)

    $(target).html("");
    sessiondata.forEach(session => {
        let dateParts = session.date.split("-");
        let jsdate = dateParts[2].substr(0, 2) + "." + dateParts[1] + "." + dateParts[0] + " " + dateParts[2].substr(3);
        $(target).append('<div class="sessiondata"><div class="dessiondata_date"> ' + jsdate + '</div><div class="session_message">' + session.message + '</div></div>');

    });
}


const buildSetup = () => {

    $("body").append(`

<div class="setup">
<div class="setup__header">Настройки</div>
<div class="setup__container"></div>
<div class="setup__footer"><div class="setup__close">Закрыть</div></div>
</div>

`)

}


const bildSetupItem = (header) => {

    const id = $('setupitem').length + 1;

    $(".setup__container").append(`

<div class="setupitem" id="setupitem`+ ($('setupitem').length + 1) + `">
<div class="setupitem__header">`+ header + `</div>
<div class="setupitem__container"></div>
<div class="setupitem__footer"><div class="setup__close">Записать</div></div>
</div>

`)
    return $("#setupitem" + id)


}



const buildconstcontent = async (object) => {

    $(object).find(".setupitem__container").html('<div class="tableheader"><div>id</div><div>Сервис</div><div>переменная</div></div>')

    const data = await fetch(window.location.href.split("?")[0] + "?debugaction=getconst" + (dev ? "&dev" : ""))
    console.log(window.location.href.split("?")[0] + "?debugaction=getconst" + (dev ? "&dev" : ""))
    const json = await data.json()

    json.forEach(item => { $(object).find(".setupitem__container").append('<div class="row"><div>' + item.row_id + '</div><div>' + item.name + '</div><div>' + prepareUid(item.uid, item.row_id) + '</div></div>') })


    function constchangef() {


    }

    $("[constchange]").click(
        function () {

            $(this).parent().html(`
            <div class="inputconst" constchangr="`+ $(this).attr("constchange") + `"> 
                <div class="inputconst_minus" sign="-1">-</div>
                <div class="inputconst_input"><input type="text" constinput value="0"></div>
                <div class="inputconst_plus" sign="1">+</div>
            </div>`)
            saveConst($(this).attr("constchange"), uid + "-0")

            $(".inputconst_minus,.inputconst_plus").click(function () {

                $(this).parent().find("input").val($(this).parent().find("input").val() * 1 + $(this).attr("sign") * 1)


                saveConst($(this).parent().attr("constchangr"), uid + "-" + $(this).parent().find("input").val())
                if ($(this).parent().find("input").val() * 1 < 0) {

                    $(this).parent().html(`<megabutton constchange="` + $(this).parent().attr("constchangr") + `">Занять</megabutton>`);
                }

            })
        }
    )





    $(".inputconst_minus,.inputconst_plus").click(function () {

        $(this).parent().find("input").val($(this).parent().find("input").val() * 1 + $(this).attr("sign") * 1)

        saveConst($(this).parent().attr("constchangr"), uid + "-" + $(this).parent().find("input").val())

        if ($(this).parent().find("input").val() * 1 < 0) {
            console.log("меньше")
            $(this).parent().html(`<megabutton constchange="` + $(this).parent().attr("constchangr") + `">Занять</megabutton>`);

        }


    })
}

const prepareUid = (luid, row_id) => {

    if (luid == null || luid == "null") {

        return `<megabutton constchange="` + row_id + `">Занять</megabutton>`
    }
    console.log(luid.split("-")[0], uid)

    if (luid.split("-")[0] == uid) {
        return ` <div class="inputconst" constchangr="` + row_id + `"> 
<div class="inputconst_minus" sign="-1">-</div>
<div class="inputconst_input"><input type="text" constinput value="`+ luid.split("-")[1] + `"></div>
<div class="inputconst_plus" sign="1">+</div>
</div>`



    }

    return '<div class="salert">Занято</div>'



}


const saveConst = async (constid, id) => {

    const data = await fetch(window.location.href.split("?")[0] + "?debugaction=saveconst&const=" + constid + "&id=" + id + (dev ? "&dev" : ""))
    // buildconstcontent(constsetup)

    //const json = await data.json()


}