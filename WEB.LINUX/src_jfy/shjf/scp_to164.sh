#!/bin/sh

# Set the current month day and year.
month=`date +%m`
day=`date +%d`
year=`date +%Y`

# Add 0 to month. This is a
# trick to make month an unpadded integer.
month=`expr $month + 0`

# Subtract one from the current day.
day=`expr $day - 1`

# If the day is 0 then determine the last
# day of the previous month.
if [ $day -eq 0 ]; then

  # Find the preivous month.
  month=`expr $month - 1`

  # If the month is 0 then it is Dec 31 of
  # the previous year.
  if [ $month -eq 0 ]; then
    month=12
    day=31
    year=`expr $year - 1`

  # If the month is not zero we need to find
  # the last day of the month.
  else
    case $month in
      1|3|5|7|8|10|12) day=31;;
      4|6|9|11) day=30;;
      2)
        if [ `expr $year % 4` -eq 0 ]; then
          if [ `expr $year % 400` -eq 0 ]; then
            day=29
          elif [ `expr $year % 100` -eq 0 ]; then
            day=28
          else
            day=29
          fi
        else
          day=28
        fi
      ;;
    esac
  fi
fi
month=`printf "%02d" $month`
day=`printf "%02d" $day`

# Print the month day and year.
yyyymmdd=$year$month$day
yyyymm=$year$month
echo $yyyymm/$yyyymmdd

# U2-gcdr_out
from_path=/usr1/data/cdr_u2/gcdr_out
to_path=/usr1/DATA/cdr/YNKd/
echo "scp -C -r $from_path/$yyyymm/$yyyymmdd/GCDR_????????_?????? 172.16.18.164:$to_path ..."
scp -C -r $from_path/$yyyymm/$yyyymmdd/GCDR_????????_?????? 172.16.18.164:$to_path

