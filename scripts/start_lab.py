#!/usr/bin/python
# -*- coding: utf8 -*-

from lxml import etree
import subprocess
import getopt
import json
import sys

def save_script(script_string,script_file):
    text_file = open(user_dir_front+"/"+script_file, "w")
    text_file.write(script_string)
    text_file.close()

lab_file = sys.argv[1]
addr_svc=sys.argv[2]
user_dir_front=sys.argv[3]
ansible_user=sys.argv[4]
ansible_pass=sys.argv[5]
frontend_ip=sys.argv[6]
addr_vpn=sys.argv[7]
addr_host_vpn=sys.argv[8]
lab = etree.parse(lab_file)

script_user = "#!/bin/bash \n\n"
script_ovs = "#!/bin/bash \n\n"
script_vm = "#!/bin/bash \n\n"
script_delnet = "#!/bin/bash \n\n"
script_addnet = "#!/bin/bash \n\n"
script_addvpn_servvm = "#!/bin/bash \n\n"
script_addvpn_frontend = "#!/bin/bash \n\n"
script_addvpn_servvpn = "#!/bin/bash \n\n"
script_delvpn_servvm = "#!/bin/bash \n\n"
script_delvpn_frontend = "#!/bin/bash \n\n"
script_delvpn_servvpn = "#!/bin/bash \n\n"
script_addwebsocket = "#!/bin/bash \n\n"
script_addpath2proxy = "#!/bin/bash \n\n"

script_del=""
script_reboot=""

# with open(lab_file) as file:
#     json.load(file)

user = lab.xpath("/lab/user/@login")[0]
lab_name = lab.xpath("/lab/name")[0].text
vpn_access = lab.xpath("/lab/tp_access")[0].text

#####################
# Création de l'utilisateur si nécessaire
#####################

script_user += "user_exists=$(awk -F':' '{print $1}' /etc/passwd | grep -w %s)\n"%user
script_user += "if [ -z $user_exists ] \nthen\n"
script_user += "\tpass=$(openssl passwd -crypt %s)\n"%user
script_user += "\t$(useradd -s /bin/bash --create-home --password $pass %s)\nfi \n"%user
script_user += "$(mkdir /home/%s/%s)"%(user,lab_name)

#####################
# Création de l'OVS
#####################

ovs_name = lab.xpath("/lab/nodes/device[@type='switch']/name")[0].text
script_ovs += "ovs-vsctl add-br %s \n"%ovs_name
script_del = "ovs-vsctl del-br %s\n"%ovs_name + script_del
    
script_ovs += "ip link set %s up \n"%ovs_name
#script_del = "ovs-vsctl dlink set  %s down\n"%ovs_name + script_del

#####################
# Set all IP and route if TP access is vpn
#####################
if vpn_access == "vpn":
    ovs_ip = lab.xpath("/lab/nodes/device[@type='switch']/vpn/ipv4")[0].text
    script_addvpn_servvm += "ip addr add %s dev %s\n"%(ovs_ip,ovs_name)

    network_lab = lab.xpath("/lab/init/network_lab")[0].text
    network_user = lab.xpath("/lab/init/network_user")[0].text
    script_addvpn_servvm += "ip route add %s via %s\n"%(network_user,frontend_ip)
    script_delvpn_servvm += "ip route del %s via %s\n"%(network_user,frontend_ip)
    addr_servvm = lab.xpath("/lab/init/serveur/IPv4")[0].text
    
    script_addvpn_frontend += "ip route add %s via %s\n"%(network_lab,addr_servvm)
    script_addvpn_frontend += "ip route add %s via %s\n"%(network_user,addr_vpn)
    script_delvpn_frontend += "ip route del %s via %s\n"%(network_lab,addr_servvm)
    script_delvpn_frontend += "ip route del %s via %s\n"%(network_user,addr_vpn)

    script_addvpn_servvpn += "ip route add %s via %s\n"%(network_lab,addr_host_vpn)

    script_delvpn_servvpn += "ip route del %s via %s\n"%(network_lab,addr_host_vpn)

#####################
# Gestion des VM Qemu
#####################
script_vm += "##########\n# Gestion des VM Qemu##########\n"
nb_vm = int(lab.xpath("count(/lab/nodes/device[@hypervisor='qemu'])"))
index_port_vnc = int(lab.xpath("/lab/init/serveur/index_interface")[0].text)

script_reboot += "if [ $# -eq 2 ] \nthen\n"

for i in range (1, nb_vm + 1):
    script_vm += "\n# VM %s\n"%i
    vm_path = "/lab/nodes/device[@hypervisor='qemu'][%s]"%i

    # création de l'image relative
    source = "/home/" + ansible_user + "/images/" + lab.xpath(vm_path + "/@image")[0]
    #dest = "/home/" + user + "/" + lab_name + "/" + lab.xpath(vm_path +"/@relativ_path")[0] + "/" + lab.xpath(vm_path +"/@image")[0] + "-" + str(i)
    dest = "/home/" + user + "/" + lab_name + "/" + lab.xpath(vm_path +"/@image")[0] + "-" + str(i)
    script_vm += "qemu-img create -b %s -f qcow2 %s \n"%(source,dest)
    script_del = "rm %s\n"%dest + script_del

    #démarrage d'une machine
    name = lab.xpath(vm_path + "/nom")[0].text
    start_vm = "qemu-system-x86_64 -machine accel=kvm:tcg -cpu Opteron_G2 -name \"%s\" -daemonize "%name
    #start_vm = "qemu-system-x86_64 -machine accel=kvm:tcg -name \"%s\" -daemonize "%name
    #start_vm = "qemu-system-x86_64 -name %s -daemonize "%name

    memory = lab.xpath(vm_path + "/system/@memory")[0]
    sys_param = "-m %s -hda %s "%(memory,dest)
    
    # connexion au réseau
    nb_interface = int(lab.xpath("count(/lab/nodes/device[@hypervisor='qemu'][%s]/interface/@type[1])"%i))
    net_param=""
    local_param=""

    for nb_int in range (1, nb_interface+1):
        ifname = lab.xpath(vm_path + "/interface[%s]/@type"%nb_int)[0]
#            print ifname
        script_vm += "ip tuntap add mode tap %s \n"%ifname
        script_del = "ip link delete %s\n"%ifname + script_del
        script_vm += "ip link set %s up \n"%ifname
        script_del = "ip link set %s down\n"%ifname + script_del
        script_vm += "ovs-vsctl add-port %s %s\n"%(ovs_name,ifname)
        script_del = "ovs-vsctl del-port %s %s\n"%(ovs_name,ifname) + script_del
        mac = lab.xpath(vm_path + "/interface[%s]/@mac_address"%nb_int)[0]
        net_param += "-net nic,macaddr=%s -net tap,ifname=%s,script=no "%(mac,ifname)

    vnc_addr = lab.xpath(vm_path + "/interface_control/@IPv4")[0]
    vnc_port = lab.xpath(vm_path + "/interface_control/@port")[0]
    script_addwebsocket += "( (nohup /home/adminVM/websockify/run %s:%s %s:%s ) & )\n"%(vnc_addr,int(vnc_port)+1000,vnc_addr,vnc_port)
    script_addpath2proxy += "curl -H \"Authorization: token $CONFIGPROXY_AUTH_TOKEN\" -X POST -d '{\"target\": \"ws://%s:%s\"}' http://localhost:82/api/routes/%s\n"%(vnc_addr,int(vnc_port)+1000,name.replace(" ","_"))

    script_del += "kill -9 `netstat -tnap | grep %s | awk -F \"[ /]*\" '{print $7}'`\n"%(int(vnc_port)+1000)

    proto = lab.xpath(vm_path + "/interface_control/@protocol")[0]
    if proto == "vnc":
        vnc_port = int(vnc_port)-5900
        access_param = "-vnc %s:%s "%(vnc_addr,vnc_port)
        local_param += "-k fr "
    else:
        access_param = "-vnc %s:%s,websocket=%s "%(vnc_addr,i+index_port_vnc,vnc_port)
    #access_param = "-vnc 0.0.0.0:%s,websocket=%s "%(i+index_port_vnc,vnc_port)
    local_param += "-localtime -usbdevice tablet "
    script_vm += start_vm + sys_param + net_param + access_param + local_param + "\n"
    script_reboot += "if [ \"$1\" = \"%s\" ]\nthen \n"%name
    script_reboot += "IMG=`ps -ef | grep qemu |grep \"%s\" | grep \"$1\" | awk '{for(i=1;i<=NF;i++){ if($i==\"-hda\"){print $((i+1))} } }'`\n"%lab_name

    script_reboot += "for i in `ps -ef | grep qemu |grep \"%s\" | grep \"$1\" | grep -v grep | awk '{print $2}'`; do kill -9 $i; done\n"%lab_name
    script_reboot += "if [ \"$2\" = \"reboot\" ]\nthen \n rm %s\n"%dest
    script_reboot += "qemu-img create -b %s -f qcow2 $IMG \nfi\n"%(source)
    script_reboot += start_vm + sys_param + net_param + access_param + local_param + "\n"
    script_reboot += "fi\n"
    
#        print script_vm
script_del = "for i in `ps -ef | grep qemu |grep \"%s\" | grep -v grep | awk '{print $2}'`; do kill -9 $i; done\n"%lab_name + script_del
script_del = "#!/bin/bash \n\n" + script_del
script_del += "rm -R /home/" + user + "/" + lab_name


script_reboot += "fi\n"

script_reboot  = "#!/bin/bash \n\n" + script_reboot 

# Ajouter une connexion internet aux machines - Mise en place d'un patch entre l'OVS du system et l'OVS du lab
if vpn_access == "vpn":
    script_addnet = "ovs-vsctl -- add-port %s patch-ovs%s-0 -- set interface patch-ovs%s-0 type=patch options:peer=patch-ovs0-%s -- add-port br0 patch-ovs0-%s  -- set interface patch-ovs0-%s type=patch options:peer=patch-ovs%s-0\n"%(ovs_name,ovs_name,ovs_name,ovs_name,ovs_name,ovs_name,ovs_name)
    script_addnet += "iptables -t nat -A POSTROUTING -s %s -o br0 -j MASQUERADE"%network_lab
    script_delnet += "ovs-vsctl del-port patch-ovs%s-0\n"%ovs_name
    script_delnet += "ovs-vsctl del-port patch-ovs0-%s\n"%ovs_name
    script_delnet += "iptables -t nat -D POSTROUTING -s %s -o br0 -j MASQUERADE"%network_lab

save_script(script_addnet,"script_addnet.sh")
save_script(script_delnet,"script_delnet.sh")
save_script(script_ovs,"script_ovs.sh")
save_script(script_vm,"script_vm.sh")
save_script(script_user,"script_user.sh")
save_script(script_del,"script_del.sh")
save_script(script_reboot,"script_reboot.sh")
save_script(script_addvpn_servvm,"script_addvpn_servvm.sh")
save_script(script_addvpn_frontend,"script_addvpn_frontend.sh")
save_script(script_addvpn_servvpn,"script_addvpn_servvpn.sh")
save_script(script_delvpn_servvm,"script_delvpn_servvm.sh")
save_script(script_delvpn_frontend,"script_delvpn_frontend.sh")
save_script(script_delvpn_servvpn,"script_delvpn_servvpn.sh")
save_script(script_addwebsocket,"script_addwebsocket.sh")
save_script(script_addpath2proxy,"script_addpath2proxy.sh")


ansible_host = "svc1 ansible_ssh_host=%s ansible_ssh_user=%s ansible_ssh_pass='%s' ansible_sudo_pass='%s'\n"%(addr_svc,ansible_user,ansible_pass,ansible_pass)
ansible_host += "frontend ansible_ssh_host=%s ansible_ssh_user=%s ansible_ssh_pass='%s' ansible_sudo_pass='%s'\n"%(frontend_ip,ansible_user,ansible_pass,ansible_pass)
ansible_host += "servvpn ansible_ssh_host=%s ansible_ssh_user=%s ansible_ssh_pass='%s' ansible_sudo_pass='%s'\n"%(addr_vpn,ansible_user,ansible_pass,ansible_pass)
ansible_host += "[server]\nsvc1\nfrontend\nservvpn"
text_file = open(user_dir_front+"/"+"script_hosts", "w")
text_file.write(ansible_host)
text_file.close()

ansible_user = "ansible svc1 -i " + user_dir_front + "/script_hosts -m script -a " + user_dir_front + "/script_user.sh -s"
ansible_ovs = "ansible svc1 -i " + user_dir_front + "/script_hosts -m script -a " + user_dir_front + "/script_ovs.sh -s"
ansible_vm = "ansible svc1 -i " + user_dir_front + "/script_hosts -m script -a " + user_dir_front + "/script_vm.sh -s"
ansible_addwebsocket = "ansible svc1 -i " + user_dir_front + "/script_hosts -m script -a " + user_dir_front + "/script_addwebsocket.sh -s"
ansible_addpath2proxy = "ansible frontend -i " + user_dir_front + "/script_hosts -m script -a " + user_dir_front + "/script_addpath2proxy.sh -s"

#####################
# Exec ansible
#####################
status_user = subprocess.call(ansible_user , shell=True)
print(status_user)
print(ansible_user)
status_ovs = subprocess.call(ansible_ovs , shell=True)
print(status_ovs)
print(ansible_ovs)
status_vm = subprocess.call(ansible_vm , shell=True)
print(status_vm)
print(ansible_vm)
status_addwebsocket = subprocess.call(ansible_addwebsocket , shell=True)
print(status_addwebsocket)
print(ansible_addwebsocket)
status_addpath2proxy = subprocess.call(ansible_addpath2proxy , shell=True)
print(status_addpath2proxy)
print(ansible_addpath2proxy)