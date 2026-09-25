##@ Alias
dep:    deploy-preprod        ## → deploy-preprod

ds:     docker-status         ## → docker-status
dsl:    docker-status-loop    ## → docker-status-loop

dsm:    docker-switch-main    ## → docker-switch-main
dsd:    docker-switch-dev     ## → docker-switch-dev
dmr:    docker-main-reload    ## → docker-main-reload
ddr:    docker-dev-reload     ## → docker-dev-reload

dmu:    docker-main-up        ## → docker-main-up
dmd:    docker-main-down      ## → docker-main-down
ddu:    docker-dev-up         ## → docker-dev-up
ddd:    docker-dev-down       ## → docker-dev-down

dmbash: docker-main-bash      ## → docker-main-bash
ddbash: docker-dev-bash       ## → docker-dev-bash
dmlogs: docker-main-logs      ## → docker-main-logs
ddlogs: docker-dev-logs       ## → docker-dev-logs

clr:    composer-lock-realign ## → composer-lock-realign
