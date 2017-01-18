#!/bin/bash

exeDir=$(cd `dirname "$0"`; pwd);
cd "$exeDir"

lastUpdateName="lastStatisticsUpdate.txt"
lastSeqNum=$(grep "^sequenceNumber=" $lastUpdateName | cut -d'=' -f2-)

curSeqNum=$(( $lastSeqNum + 1 ))
curTimestamp=`date +"%Y-%m-%dT%H:%M:%S"`

logFileName="logsStatistics/log.$curSeqNum"

echo `date +"%Y-%m-%d %H:%M"`" - Start processing sequence number $curSeqNum" > $logFileName
php update_statistics.php 2>&1 | tee -a $logFileName
echo `date +"%Y-%m-%d %H:%M"`" - Finish processing sequence number $curSeqNum" | tee -a $logFileName

echo "sequenceNumber=$curSeqNum" > $lastUpdateName
echo "timestamp=$curTimestamp" | tee -a $lastUpdateName
