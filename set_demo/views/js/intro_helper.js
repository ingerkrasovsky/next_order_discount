//Extended scripts related to intro library and other useful tools

/**
 * Set cookie
 *
 * @param cname
 * @param cvalue
 * @param exdays
 */
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires +";path=/";
}

/**
 * Get cookie
 * @param cname
 * @returns {string}
 */
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}

/**
 * Get Intro instance from cookie
 *
 * @returns {*}
 */
function getIntroInstance() {
    if (getCookie('intro')) {
        try {
            return JSON.parse(getCookie('intro'));
        } catch (error) {
            // Recover from malformed cookie values left by older versions/manual edits.
            setCookie('intro', JSON.stringify({}), 1);
            return {};
        }
    }
    else {
        return {};
    }
}

/**
 * Stop intro
 *
 * @param introName
 * @param introInstance
 */
function stopIntro(introName, introInstance) {
    jQuery('.set-demo-controls__button--tutorials').show();

    var introData = getIntroInstance();
    introData[introName] = introInstance._currentStep;
    introData['tutorial'] = 'stop';
    setCookie('intro', JSON.stringify(introData), 1);
    showTutorialPanel();
}

/**
 * Continue intro
 *
 * @param introName
 * @param introInstance
 */
function continueIntro(introName, introInstance) {
    try {
        hideTutorialPanel();
        var introData = getIntroInstance();
        introData['tutorial'] = 'start';
        setCookie('intro', JSON.stringify(introData), 1);
        if (introData[introName] === 0) {
            introInstance.start();
        } else {
            introInstance.goToStep(introData[introName]).start();
        }
    }
    catch (e) {
        introInstance.exit();
        introInstance.start();
        introData['tutorial'] = 'start';
        setCookie('intro', JSON.stringify(introData), 1);
    }

    jQuery('.set-demo-controls__button--tutorials').hide();
}

/**
 * Check if can intro can be started
 *
 * @returns {boolean}
 */
function ifCanStartIntro() {
    var introData = getIntroInstance();
    var tutorialButton = jQuery('.set-demo-controls__button--tutorials').first();
    if (introData['tutorial'] == undefined) {
        introData['tutorial'] = 'start';
        setCookie('intro', JSON.stringify(introData), 1);
    }

    if (introData['tutorial'] == "start") {
        tutorialButton.hide();
        return true;
    }

    if (introData['tutorial'] == "stop") {
        tutorialButton.show();
        return false;
    }
}

/**
 * Check if tutorial panel can be displayed
 *
 * @returns {boolean}
 */
function ifCanShowTutorialPanel() {
    var introData = getIntroInstance();

    if (introData.tutorial == undefined) {
        introData['tutorial'] = 'stop';
        introData['tutorial_panel'] = 'opened';
        setCookie('intro', JSON.stringify(introData), 1);
    }

    if (introData.tutorial == "start") {
        return false;
    }

    if (window.innerWidth < 1024) {
        return false;
    }

    if ((introData['tutorial_panel'] == undefined) || (introData['tutorial_panel'] == 'opened'))  {
        introData['tutorial_panel'] = 'opened';
        setCookie('intro', JSON.stringify(introData), 1);
        return true;
    } else {
        return false;
    }
}

/**
 * Show tutorial panel
 */
function showTutorialPanel() {
    jQuery('body').addClass('is-set-tutorial-open');
}

/**
 * Hide tutorial panel
 */
function hideTutorialPanel() {
    jQuery('body').removeClass('is-set-tutorial-open');
}

/**
 * Find one string in another
 * @param string
 * @param find
 * @returns {boolean}
 */
function strstr(string, find) {
    if (string.indexOf(find) === -1) return false;
    else return true;
}

/**
 * Check if tutorial is started and throw exception to prevent another tutorial to be launched
 */
function checkIfTutorialIsStarted() {
    var intro = getIntroInstance();
    if (jQuery('.introjs-overlay').css('display') == 'block' || intro.tutorial == 'start') {
        throw new Error("Tutorial is already started");
    }
}

/**
 * Continue tutorial
 */
function continueTutorial() {
    var introData = getIntroInstance();
    hideTutorialPanel();
    introData['tutorial'] = 'start';
    introData['tutorial_panel'] = 'closed';
    setCookie('intro', JSON.stringify(introData), 1);
}
