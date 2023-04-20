'use strict';

import $ from 'jquery';

require("../base-personal-data");

app.service('dataService', function () {
    this.folders = {};
});

app.controller("EntityInformationModalCtrl", function ($scope) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.folderDoc;
        $scope.folders = args.folders;
    });

    $scope.getStatut = entity => entity.b_prive ? "Privé" : "Partagé";

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.getDocumentsLength = (folderId) => {
        if (folderId) {
            const folderEdit = $scope.folders.find(x => (x.id === folderId));
            return folderEdit.documents.length;
        }
    };
});

app.controller("EntityRenameModalCtrl", function ($scope, $http, dataService) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.folderDoc;
        $scope.newName = $scope.entity.nom;
    });

    $scope.getNgModel = () => $scope.entity.nom;

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.rename = (newName) => {
        if (newName && $scope.entity.nom !== newName) {
            const target = !!$scope.entity.is_folder ? 'folder' : 'document';
            $http.patch($scope.getRoute("api_" + target + "_rename", $scope.entity.id), {name: newName}
            ).then(response => {
                const entity = response.data;
                if (entity.is_folder) {
                    const folderEdit = dataService.folders.find(x => (x.id === entity.id));
                    folderEdit.nom = entity.nom;
                }
                $scope.entity.nom = entity.nom;
                const $renameModal = $("#renameModal");
                $renameModal.modal("hide");
                $renameModal.find("input").val("");
            });
        }
    };

    $scope.name = () => {
        return $scope.newName;
    };

    $scope.onChange = ($event) => {
        $scope.newName = $event.target.value;
    };
});

app.controller("EntityMoveIntoFolderModalCtrl", function ($scope, $http, $rootScope, dataService) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.folderDoc;
        $scope.entities = args.entities;
        $scope.folders = args.folders;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.moveIntoFolder = folderId => {
        const route = !!$scope.entity.is_folder ? "api_folder_put_in_folder" : "api_document_put_in_folder";
        $http.patch($scope.getRoute(route, {
            "id": $scope.entity.id,
            "dossierId": folderId
        }))
            .then(response => {
                const entity = response.data;

                const currentFolder = getCurrentFolder();
                if (null !== currentFolder) {
                    currentFolder.documents.splice(currentFolder.documents.indexOf($scope.entity), 1);
                }

                $scope.entities.splice($scope.entities.indexOf($scope.entity), 1);

                const folderDest = $scope.folders.find(x => (x.id === folderId));
                if (!!entity.is_folder) {
                    folderDest.sous_dossiers.push(entity);
                } else {
                    folderDest.documents.push(entity);
                }
                $("#moveIntoFolderModal").modal("hide");
            });
    };

    $scope.getOut = () => {
        const currentFolder = getCurrentFolder();
        const route = !!$scope.entity.is_folder ? "api_folder_get_out_from_folder" : "api_document_get_out_from_folder";
        $http.patch($scope.getRoute(route, $scope.entity.id))
            .then(() => {
                $scope.entities.splice($scope.entities.indexOf($scope.entity), 1);
                if (currentFolder) {
                    currentFolder.documents.splice(currentFolder.documents.indexOf($scope.entity), 1);
                }
                $("#moveIntoFolderModal").modal("hide");
            });
    };

    const getCurrentFolder = () => {
        let currentFolder = null;
        if (!$scope.entity.is_folder && $scope.entity.folder_id !== null) {
            currentFolder = $scope.folders.find(x => (x.id === $scope.entity.folder_id));
        }
        return currentFolder;
    };

    $scope.inFolder = () => $scope.entity.folder_id !== null;

    $scope.getDocumentsLength = (folderId = null) => {
        if (folderId) {
            const folderEdit = $scope.folders.find(x => (x.id === folderId));
            return folderEdit.documents.length;
        }
        let count = 0;
        $scope.entities.forEach(entity => {
            if (!entity.is_folder) {
                count++;
            }
        });
        return count;
    };

    $scope.isDisabledClass = (folderId = null) => disabledClick(folderId) ? "disabled" : "";

    $scope.inSameFolder = folder => {
        const folderParentId = folder.dossier_parent_id;
        const entityParentId = !$scope.entity.is_folder ? $scope.entity.folder_id : $scope.entity.dossier_parent_id;

        return (!entityParentId && !folderParentId) || entityParentId === folderParentId;
    };

    const disabledClick = (folderId = null) => {
        if (!$scope.entity.is_folder) {
            return folderId === $scope.entity.folder_id;
        }

        // Le dossier est égal au dossier contenant ou dans le dossier parent est null et le folder aussi
        return (null === $scope.entity.dossier_parent_id && null === folderId) || $scope.entity.id === folderId;
    }

    $scope.getMessage = (folder = null) => {
        const $moveModal = $('.move-modal');
        const documentMessageMoveIn = $moveModal.data('messageDocumentIn');
        const documentMessageMoveOut = $moveModal.data('messageDocumentOut');
        const folderMessageMoveIn = $moveModal.data('messageFolderIn');
        const folderMessageMoveOut = $moveModal.data('messageFolderOut');
        const moveInMessage = $moveModal.data('messageMoveIn');

        if ((null === folder && disabledClick(null)) || (null !== folder && disabledClick(folder.id))) {
            return;
        }
        if (folder) {
            if ($scope.entity.is_folder) {
                return folderMessageMoveIn + " \"" + $scope.entity.nom + "\" " + moveInMessage + " \"" + folder.nom + "\"?";
            }
            return documentMessageMoveIn + " \"" + $scope.entity.nom + "\" " + moveInMessage + " \"" + folder.nom + "\"?";
        }
        if ($scope.entity.is_folder) {
            return folderMessageMoveOut + " \"" + $scope.entity.nom + "\"?";
        }
        return documentMessageMoveOut + " \"" + $scope.entity.nom + "\"?";
    };

    $scope.getNom = folder => folder.nom;
});

app.controller("DocumentsListCtrl", function ($scope, $http, $filter, $location, dataService) {
    $scope.refresh = function () {
        $http.get($scope.getRoute("api_document_list_from_folder", {
            "beneficiaryId": beneficiaireId,
            "folderId": $scope.dossierId
        })).success(function (data) {
            const $folderControl = $("#folderControl");
            if ($scope.dossierId !== -1) {
                $folderControl.show();
            } else {
                $folderControl.hide();
            }

            $scope.foldersDocs = data;
            $scope.foldersDocsFiltered = data;
            $scope.currentPage = 1;
            $scope.pageSize = 10;
            $scope.numberOfPages = function () {
                return Math.ceil($scope.foldersDocs.length / $scope.pageSize);
            };
            // To make uppy work
            $("#personalDataBody").data("folderId", $scope.dossierId);
        });
        $http.get($scope.getRoute("api_folder_list", {
            "beneficiaryId": beneficiaireId,
        })).success(function (data) {
            $scope.folders = data;
            dataService.folders = data;
        });
        if ($scope.parentFolderId === undefined && $scope.dossierId !== -1) {
            $http.get($scope.getRoute("api_folder_get_entity", $scope.dossierId))
                .then(response => {
                    const entity = response.data;
                    $scope.parentFolderId = entity.dossier_parent_id;
                    $scope.folder = entity;
                });
        }
    };

    $scope.getShareIconClass = function (folderDoc) {
        if (folderDoc.b_prive) {
            return "text-secondary";
        }
        return "vault-green";
    };

    $scope.getLockIconClass = function (folderDoc) {
        if (folderDoc.b_prive) {
            return " text-primary";
        }
        return "-open text-secondary";
    };

    $scope.toggleAccess = function (folderDoc) {
        if ($scope.isFolder(folderDoc)) {
            $http.patch($scope.getRoute("api_folder_toggle_access", folderDoc.id)).success(function (data) {
                const folderDocEdit = $scope.foldersDocs.find(function (x) {
                    return (x.id === folderDoc.id && folderDoc.is_folder);
                });
                folderDocEdit.b_prive = data.b_prive;
            });
        } else {
            $http.patch($scope.getRoute("api_document_toggle_access", folderDoc.id)).success(function (data) {
                const folderDocEdit = $scope.foldersDocs.find(function (x) {
                    return (x.id === folderDoc.id && !folderDoc.is_folder);
                });
                folderDocEdit.b_prive = data.b_prive;
            });
        }
    };

    $scope.isFolder = function (folderDoc) {
        if (typeof folderDoc === "undefined") {
            return false;
        }
        return "documents" in folderDoc;
    };

    $scope.isInFolder = function (folderDoc) {
        if ($scope.isFolder(folderDoc)) {
            return false;
        }
        return typeof folderDoc.dossier === "undefined";
    };

    $scope.getOriginal = function (folderDoc, archivePath) {
        return archivePath + "originals/" + encodeURIComponent(folderDoc.nom);
    };

    $scope.getThumb = function (folderDoc) {
        if ($scope.isFolder(folderDoc)) {
            if (folderDoc.dossier_image !== "") {
                return "/uploads/client/" + folderDoc.dossier_image;
            }

            const dossier = require("../../images/icons/dossier.png");
            return dossier.default;
        } else {
            return folderDoc.thumb;
        }
    };

    $scope.sortirDossier = function (doc) {
        $http.patch($scope.getRoute("api_document_get_out_from_folder", doc.id)).success(function () {
            $scope.foldersDocs.splice($scope.foldersDocs.indexOf(doc), 1);
        });
    };

    $scope.delete = function (folderDoc) {
        if ($scope.isFolder(folderDoc)) {
            $http.delete($scope.getRoute("api_folder_delete", folderDoc.id)).success(function () {
                $scope.refresh();
            });
        } else {
            $http.delete($scope.getRoute("api_document_delete", folderDoc.id)).success(function () {
                $scope.foldersDocs.splice($scope.foldersDocs.indexOf(folderDoc), 1);
            });
        }
    };

    $scope.reportAbuse = function (folderDoc) {
        $http.patch($scope.getRoute("api_document_report_abuse", folderDoc.id)).success(function () {
            $scope.foldersDocs.splice($scope.foldersDocs.indexOf(folderDoc), 1);
        });
    };

    $scope.createDossier = () => {
        if (-1 === $scope.dossierId) {
            $http.get($scope.getRoute("re_app_dossier_add", {
                id: beneficiaireId,
                "dossier-parent": $scope.dossierId
            })).then(response => {
                $("#newFolderModalForm").html($(response.data));
            });
        } else {
            $http.get($scope.getRoute("re_app_dossier_add_subfolder", $scope.dossierId))
                .then(response => {
                    $("#newFolderModalForm").html($(response.data));
                });
        }
    };

    $scope.isInFolder = function () {
        return $scope.dossierId !== -1;
    };

    $scope.$watch(function () {
        return $location.search().folder;
    }, function (newVal) {
        $scope.dossierId = newVal || -1;
        $scope.refresh();
    });

    $scope.getAllDocsInFolders = function () {
        let arRet = [];
        $.each($scope.foldersDocs, function (key, folderDoc) {
            if (!$scope.isFolder(folderDoc)) {
                arRet.push(folderDoc);
            } else {
                $.each(folderDoc["documents"], function (key2, doc) {
                    arRet.push(doc);
                });
            }
        });
        return arRet;
    };

    $scope.$watch("searchQuery", function (newVal, oldVal) {
        if (newVal) {
            if (newVal.length < 3) {
                $scope.foldersDocsFiltered = $scope.foldersDocs;
            } else {
                // this is the JS equivalent of "phones | filter: newVal"
                $scope.foldersDocsFiltered = $filter("filter")($scope.getAllDocsInFolders(), newVal);
            }
        }
    });

    $scope.setFolder = function (folderId) {
        $location.search("folder", folderId);
        if (folderId !== null) {
            $scope.folder = $scope.folders.find((x) => {
                return x.id === folderId;
            });
            $scope.parentFolderId = $scope.folder.dossier_parent_id;
        }
    };

    $scope.openDocument = function (document) {
        window.open($scope.getRoute("api_document_show", document.id), "_blank");
    };

    $scope.activateLine = function (folderDoc, event) {
        $(".tableLine").removeClass("tableLine-active");
        $(angular.element(event.currentTarget)[0]).children("td").each(function () {
            $(this).addClass("tableLine");
            $(this).addClass("tableLine-active");
        });
        $scope.$broadcast("lineActivated", {
            folderDoc: folderDoc,
            entities: $scope.foldersDocs,
            folders: $scope.folders
        });
        $scope.currentEntity = folderDoc;
    };

    $scope.onOverFile = function (event) {
        $(event.target).children("td").each(function () {
            $(this).addClass("tableLine");
            $(this).addClass("tableLine-hover");
        });
    };

    $scope.onDropFile = function (event, ui) {
        const folderDoc = JSON.parse(ui.draggable.attr("data-folderDoc"));
        const folder = JSON.parse($(event.target).attr("data-folderDoc"));

        const route = !folderDoc.is_folder ? "api_document_put_in_folder" : "api_folder_put_in_folder";

        $http.patch($scope.getRoute(route, {
            "id": folderDoc.id,
            "dossierId": folder.id
        }))
            .then(response => {
                const entity = response.data;
                const condition = !folderDoc.is_folder ? !entity.is_folder : entity.is_folder;
                const folderDocEdit = $scope.foldersDocs.find(function (x) {
                    return (x.id === entity.id && condition);
                });

                $scope.foldersDocs.splice($scope.foldersDocs.indexOf(folderDocEdit), 1);
            });
    };

    $scope.onOutFile = function () {
        $(".tableLine").removeClass("tableLine-hover");
    };

    $scope.getMessageDelete = folderDoc => {
        const $deleteButton = $('.delete-button');
        const deleteMessage = folderDoc.is_folder ? $deleteButton.data('messageFolder') : $deleteButton.data('messageDocument');

        return deleteMessage + " : \"" + folderDoc.nom + "\"?"
    }


    $scope.getShareExpirationMessage = function (folderDoc) {
        const $deleteButton = $('.shared-delete-button');
        const deleteMessage = folderDoc.is_folder ? $deleteButton.data('messageFolder') : $deleteButton.data('messageDocument');

        return deleteMessage.replace('%daysCount%', folderDoc.daysBeforeSharingExpires);
    };


    $scope.folderIsEmpty = (entity) => {
        if ($scope.folders !== undefined) {
            return subFolderIsEmpty(entity);
        }
        return true;
    }

    const subFolderIsEmpty = (entity) => {
        let isEmpty = true;
        if (0 < entity.documents.length) {
            return false;
        }
        if (0 < entity.sous_dossiers.length) {
            angular.forEach(entity.sous_dossiers, (value) => {
                let sousDossier = $scope.folders.find(x => x.id === value.id);
                if (!!isEmpty) {
                    if (!subFolderIsEmpty(sousDossier)) {
                        isEmpty = false;
                    }
                }
            });
        }
        return isEmpty;
    }
});

app.controller("newFolderModalCtrl", function ($scope, $http) {
    $scope.submitForm = () => {
        const formSerialize = $("#newFolderModalForm").serialize();
        const dossierId = $scope.dossierId;
        if (-1 === $scope.dossierId) {
            $http({
                method: 'POST',
                url: $scope.getRoute("re_app_dossier_add", beneficiaireId),
                data: formSerialize,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            })
                .then(() => {
                    successCreateFolder()
                }, response => {
                    $("#newFolderModalForm").html($(response.data));
                });
        } else {
            $http({
                method: 'POST',
                url: $scope.getRoute("re_app_dossier_add_subfolder", dossierId),
                data: formSerialize,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            })
                .then(() => {
                    successCreateFolder();
                }, response => {
                    $("#newFolderModalForm").html($(response.data));
                });
        }
    };

    const successCreateFolder = () => {
        $("#newFolderModal").modal("hide");

        $("#alertSuccessMessage").append('Dossier créé.');
        $("#alertSuccess").fadeTo(2000, 500);
        setTimeout(() => {
            $("#alertSuccess").slideUp(500, () => {
                $("#alertSuccessMessage").html('');
            });
        }, 5000);

        $scope.refresh();
    }
});
