<?php


use Bat\FileSystemTool;
use QuickPdo\QuickPdo;

require_once "../init.php";


if (true === User::isConnected()) {


    $userId = User::getId();

    if (array_key_exists('nom', $_POST)) {


        $urlPhoto = null;
        if (array_key_exists('url_photo', $_FILES) && strlen($_FILES['url_photo']['name']) > 0) {
            /**
             * It reforges the image, to eliminate potential backdoor nested in the user's image
             */
            $dest = APP_ROOT_DIR . "/www/img/users/" . $userId;
            FileSystemTool::mkdir($dest);
            $path = SecureImageUploader::upload($_FILES['url_photo'], $dest, null, 300);
            $urlPhoto = substr($path, strlen(APP_ROOT_DIR . "/www"));
        }

        // now insert $urlPhoto in your database...


        $date = sprintf('%s-%02s-%02s', $_POST['date_naissance_annee'], $_POST['date_naissance_mois'], $_POST['date_naissance_jour']);
        $paysId = (int)$_POST['pays'];
        if (0 === $paysId) {
            $paysId = null;
        }
        $niveauxId = (int)$_POST['niveau'];
        if (0 === $niveauxId) {
            $niveauxId = null;
        }


        $instruments = (array_key_exists('instruments', $_POST)) ? $_POST['instruments'] : [];
        $styles = (array_key_exists('styles', $_POST)) ? $_POST['styles'] : [];


        $what = [
            'nom' => (string)$_POST['nom'],
            'prenom' => (string)$_POST['prenom'],
            'sexe' => (string)$_POST['sexe'],
            'date_naissance' => (string)$date,
            'code_postal' => (string)$_POST['code_postal'],
            'ville' => (string)$_POST['ville'],
            'pays_id' => $paysId,
            'niveaux_id' => $niveauxId,
            'biographie' => (string)$_POST['biographie'],
            'influences' => (string)$_POST['influences'],
            'prochains_concerts' => (string)$_POST['prochains_concerts'],
            'site_internet' => (string)$_POST['site_internet'],
            'newsletter' => (string)$_POST['newsletter'],
            'show_sexe' => (string)$_POST['show_sexe'],
            'show_date_naissance' => (string)$_POST['show_date_naissance'],
            'show_niveau' => (string)$_POST['show_niveau'],
        ];

        if (null !== $urlPhoto) {
            $what['url_photo'] = $urlPhoto;
        }

        QuickPdo::update('users', $what, [
            'id' => $userId,
        ]);


        // now we need to create one entry for every instrument/style_musicaux chosen by the user.
        foreach ($instruments as $instrumentId) {
            QuickPdo::replace('users_has_instruments', [
                'users_id' => $userId,
                'instruments_id' => $instrumentId,
            ]);
        }

        foreach ($styles as $styleId) {
            QuickPdo::replace('users_has_styles_musicaux', [
                'users_id' => $userId,
                'styles_musicaux_id' => $styleId,
            ]);
        }
    }


    $item = QuickPdo::fetch("
select 
      u.*,
      p.nom as pays,
      n.nom as niveaux
from users u 
    inner join pays p on p.id=u.pays_id
    inner join niveaux n on n.id=u.niveaux_id
where u.id=" . (int)$userId);


    $section = array_key_exists('section', $_GET) ? $_GET['section'] : "main";
    Template::printHtmlTop();


    $allowedSections = [
        'info',
        'recap',
    ];

    $allowedModes = [
        'all',
        'concours',
    ];


    if (!in_array($section, $allowedSections)) {
        $section = 'info';
    }


    function getProfilLink($section, $concours = 0)
    {
        return url("/profil?section=$section&onglet=" . $concours);
    }







    $userInstruments = Cachos::getUsersInstrumentsList($userId);
    $userStyles = Cachos::getUsersStylesMusicauxList($userId);


    $countries = Cachos::getCountryList();
    $niveaux = Cachos::getNiveauxList();
    $instruments = Cachos::getInstrumentsList();
    $styles_musicaux = Cachos::getStylesMusicauxList();
    array_unshift($countries, "Choisir");
    array_unshift($niveaux, "Choisir");
    array_unshift($instruments, "Choisir");
    array_unshift($styles_musicaux, "Choisir");




    function sel($value, $itemValue, $isRadio = false)
    {
        if ($value === $itemValue) {
            if (false === $isRadio) {
                return 'selected="selected"';
            }
            return 'checked="checked"';
        }
        return "";
    }

    $date_naissance_jour = 1;
    $date_naissance_mois = 1;
    $date_naissance_annee = 1900;
    $years_min = 1900;
    $years_max = (int)date('Y');
    if (null !== $item['date_naissance']) {
        list($date_naissance_jour, $date_naissance_mois, $date_naissance_annee) = explode('-', $item['date_naissance']);
        $date_naissance_jour = (int)$date_naissance_jour;
        $date_naissance_mois = (int)$date_naissance_mois;
        $date_naissance_annee = (int)$date_naissance_annee;
    }
    $months = [
        1 => 'janvier',
        2 => 'février',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'août',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'décembre',
    ];




    ?>
    <body>
    <?php

    Template::printSiteHeader();


    ?>
    <main class="page-profil" id="page-profil">
        <?php
        if (1 === (int)$item['active']) {
            ?>
            <nav class="sub-menu">
                <ul>
                    <li><a class="menulink <?php echo ('info' === $section) ? "selected" : ''; ?>"
                           href="<?php echo htmlspecialchars(getProfilLink('info')); ?>">Mes informations</a>
                    </li>
                    <li><a class="menulink <?php echo ('recap' === $section) ? "selected" : ''; ?>"
                           href="<?php echo htmlspecialchars(getProfilLink('recap')); ?>">Ma fiche</a></li>
                </ul>
            </nav>
            <section class="section-profil">
            
                <form id="form-profil" action="#posted" method="post" class="o-form form-profil"
                      enctype="multipart/form-data">



                    <div class="row">
                        <span class="label">Pseudo</span>
                        <span><?php echo $item['pseudo']; ?></span>
                    </div>
                    <div class="row">
                        <span class="label">Avatar</span>
                        <input type="file" name="url_photo">
                        <img width="80" src="<?php echo htmlspecialchars(url($item['url_photo'])); ?>"
                             alt="<?php echo htmlspecialchars($item['pseudo']); ?>">
                    </div>
                    <div class="row">
                        <span class="label">Newsletter</span>
                        <select name="newsletter">
                            <option value="n" <?php echo sel('n', $item['newsletter']); ?>>Non</option>
                            <option value="y" <?php echo sel('y', $item['newsletter']); ?>>Oui</option>
                        </select>
                    </div>
                    <div class="row">
                        <span class="label">Nom</span>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($item['nom']); ?>">
                    </div>
                    <div class="row">
                        <span class="label">Prénom</span>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($item['prenom']); ?>">
                    </div>
                    <div class="row">
                        <span class="label">Sexe</span>
                        <div class="control-container">
                            <select name="sexe">
                                <option value="f" <?php echo sel('f', $item['sexe']); ?>>Femme</option>
                                <option value="h" <?php echo sel('h', $item['sexe']); ?>>Homme</option>
                            </select>
                            <div class="row_visible">
                                <span class="visible_label">Visible sur le site:</span>
                                <span class="visible_label_item">oui</span>
                                <input type="radio" class="autowidth" name="show_sexe"
                                       value="y" <?php echo sel($item['show_sexe'], 'y', true); ?>>
                                <span class="visible_label_item">non</span>
                                <input type="radio" class="autowidth" name="show_sexe"
                                       value="n" <?php echo sel($item['show_sexe'], 'n', true); ?>>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <span class="label">Date de naissance</span>
                        <div class="control-container">
                            <select class="autowidth" name="date_naissance_jour">
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                    <option
                                        value="<?php echo $i; ?>" <?php echo sel($i, $date_naissance_jour); ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>

                            <select class="autowidth" name="date_naissance_mois">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option
                                        value="<?php echo $i; ?>" <?php echo sel($i, $date_naissance_mois); ?>><?php echo $months[$i]; ?></option>
                                <?php endfor; ?>
                            </select>
                            <select class="autowidth" name="date_naissance_annee">
                                <?php for ($i = $years_max; $i >= $years_min; $i--): ?>
                                    <option
                                        value="<?php echo $i; ?>" <?php echo sel($i, $date_naissance_annee); ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="row_visible">
                                <span class="visible_label">Visible sur le site:</span>
                                <span class="visible_label_item">oui</span>
                                <input type="radio" class="autowidth" name="show_date_naissance"
                                       value="y" <?php echo sel($item['show_date_naissance'], 'y', true); ?>>
                                <span class="visible_label_item">non</span>
                                <input type="radio" class="autowidth" name="show_date_naissance"
                                       value="n" <?php echo sel($item['show_date_naissance'], 'n', true); ?>>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <span class="label">Code postal</span>
                        <input type="text" name="code_postal"
                               value="<?php echo htmlspecialchars($item['code_postal']); ?>">
                    </div>
                    <div class="row">
                        <span class="label">Ville</span>
                        <input type="text" name="ville" value="<?php echo htmlspecialchars($item['ville']); ?>">
                    </div>
                    <div class="row">
                        <span class="label">Pays</span>
                        <select name="pays">
                            <?php foreach ($countries as $id => $country): ?>
                                <option
                                    value="<?php echo $id; ?>" <?php echo sel($id, (int)$item['pays_id']); ?>><?php echo $country; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <div class="row">
                        <span class="label">Je suis musicien</span>
                        <div class="control-container">
                            <select name="niveau">
                                <?php foreach ($niveaux as $id => $niveau): ?>
                                    <option
                                        value="<?php echo $id; ?>" <?php echo sel($id, (int)$item['niveaux_id']); ?>><?php echo $niveau; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="row_visible">
                                <span class="visible_label">Visible sur le site:</span>
                                <span class="visible_label_item">oui</span>
                                <input type="radio" class="autowidth" name="show_niveau"
                                       value="y" <?php echo sel($item['show_niveau'], 'y', true); ?>>
                                <span class="visible_label_item">non</span>
                                <input type="radio" class="autowidth" name="show_niveau"
                                       value="n" <?php echo sel($item['show_niveau'], 'n', true); ?>>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <span class="label">Instruments</span>
                        <select id="select-instrument">
                            <?php foreach ($instruments as $id => $instrument): ?>
                                <option
                                    value="<?php echo $id; ?>"><?php echo $instrument; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="add" id="btn-add-instrument">Ajouter cet instrument</button>
                    </div>

                    <div class="row">
                        <span class="label"></span>
                        <ul class="dynamic-items" id="instrument-target">
                            <?php foreach ($userInstruments as $id => $name): ?>
                                <li><input type="hidden" name="instruments[]"
                                           value="<?php echo $id; ?>"><span><?php echo $name; ?></span>
                                    <button class="action-remove">Supprimer</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="row">
                        <span class="label">Styles musicaux</span>
                        <select id="select-style">
                            <?php foreach ($styles_musicaux as $id => $style): ?>
                                <option
                                    value="<?php echo $id; ?>"><?php echo $style; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="add" id="btn-add-style">Ajouter ce style</button>
                    </div>
                    <div class="row">
                        <span class="label"></span>
                        <ul class="dynamic-items" id="style-target">
                            <?php foreach ($userStyles as $id => $name): ?>
                                <li><input type="hidden" name="styles[]"
                                           value="<?php echo $id; ?>"><span><?php echo $name; ?></span>
                                    <button class="action-remove">Supprimer</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>


                    <div class="row">
                        <span class="label">Biographie</span>
                        <textarea name="biographie"><?php echo $item['biographie']; ?></textarea>
                    </div>

                    <div class="row">
                        <span class="label">Influences</span>
                        <textarea name="influences"><?php echo $item['influences']; ?></textarea>
                    </div>

                    <div class="row">
                        <span class="label">Prochains concerts</span>
                        <textarea name="prochains_concerts"><?php echo $item['prochains_concerts']; ?></textarea>
                    </div>

                    <div class="row">
                        <span class="label">Site internet</span>
                        <input type="text" name="site_internet"
                               value="<?php echo htmlspecialchars($item['site_internet']); ?>">
                    </div>

                    <div class="submit">
                        <input id="input-submit" class="input-submit" type="submit" value="Renvoyer">
                    </div>
                </form>

            </section>
            <?php

        } else {
            ?>
            <p class="block centered warning">
                Ce profil a été désactivé
            </p>
            <?php


        }
        ?>
    </main>
    <div class="template">
        <li id="template-instrument"><input type="hidden" name="instruments[]" value=""><span>any</span>
            <button class="action-remove">Supprimer</button>
        </li>
        <li id="template-style"><input type="hidden" name="styles[]" value=""><span>any</span>
            <button class="action-remove">Supprimer</button>
        </li>
    </div>
    <script>


        var selectInstr = document.getElementById('select-instrument');
        var targetInstr = document.getElementById('instrument-target');

        var selectStyle = document.getElementById('select-style');
        var targetStyle = document.getElementById('style-target');

        var instrIds = [];
        var stylesIds = [];


        function toArr(matches) {
            return Array.prototype.slice.call(matches);
        }

        function fetchValues(target, arr) {
            toArr(target.querySelectorAll('input')).forEach(function (el) {
                arr.push(el.value);
            });
        }


        fetchValues(targetInstr, instrIds);
        fetchValues(targetStyle, stylesIds);


        function _getTemplate(id) {
            var tpl = document.getElementById(id);
            return tpl.cloneNode(true);
        }

        function removeItem(el) {
            el.parentNode.parentNode.removeChild(el.parentNode);
        }

        function getTemplate(tplId, value, name) {
            var tplClone = _getTemplate(tplId);
            tplClone.removeAttribute('id');
            tplClone.querySelector('input').setAttribute('value', value);
            tplClone.querySelector('span').textContent = name;
            return tplClone;
        }


        function getSelectLabel(selectEl, val) {
            var el = selectEl.querySelector("option[value='" + val + "']");
            return el.textContent;
        }

        function handleDynamicList(e, selectEl, arrIds, tplId, targetContainer) {
            e.preventDefault();
            var val = parseInt(selectEl.value);
            if (
                0 !== val &&
                'undefined' !== typeof val &&
                -1 === arrIds.indexOf(val)
            ) {
                var name = getSelectLabel(selectEl, val);
                var tplClone = getTemplate(tplId, val, name);
                targetContainer.appendChild(tplClone);
                arrIds.push(val);
            }
        }

        document.getElementById('page-profil').addEventListener('click', function (e) {

            if (e.target.classList.contains('action-remove')) {
                e.preventDefault();
                removeItem(e.target);
            }
            else if (e.target.hasAttribute('id')) {
                if ('btn-add-instrument' === e.target.id) {
                    e.preventDefault();
                    handleDynamicList(e, selectInstr, instrIds, 'template-instrument', targetInstr);
                }
                else if ('btn-add-style' === e.target.id) {
                    e.preventDefault();
                    handleDynamicList(e, selectStyle, stylesIds, 'template-style', targetStyle);
                }
            }

        });


    </script>

    <?php


    Template::printHtmlBottom();


} else {
    require_once __DIR__ . "/accueil.php";
}