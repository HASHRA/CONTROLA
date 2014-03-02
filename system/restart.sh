#/bin/bash
ssh pi@192.168.0.214 -i /root/.ssh/id_rsa "python control.py 0 && sleep 1 && python control.py 1"