#!/bin/bash

#################################################################
# WoltLab Package Unpacker Script
# Package: de.julian-pfeil.urlshort.featuredLinks
# Author: Sunny C.
#################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Package information
PACKAGE_NAME="info.benjaro.urlshort.affiliate"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SRC_DIR="${SCRIPT_DIR}/_extracted"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab Package Unpacker${NC}"
echo -e "${GREEN}Package: ${PACKAGE_NAME}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Clean src directory
echo -e "${YELLOW}[1/5] Cleaning source directory...${NC}"
rm -rf "${SRC_DIR}"
mkdir -p "${SRC_DIR}"

# Extract files.tar
echo -e "${YELLOW}[2/5] Extracting files.tar...${NC}"
if [ -f "${SCRIPT_DIR}/files.tar" ]; then
    mkdir -p "${SRC_DIR}/files"
    cd "${SRC_DIR}/files"
    tar -xf "${SCRIPT_DIR}/files.tar"
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ files.tar extracted ($(find ${SRC_DIR}/files -type f | wc -l) files)${NC}"
else
    echo -e "${RED}✗ files.tar not found${NC}"
fi

# Extract files_urlshort.tar
echo -e "${YELLOW}[3/5] Extracting files_urlshort.tar...${NC}"
if [ -f "${SCRIPT_DIR}/files_urlshort.tar" ]; then
    mkdir -p "${SRC_DIR}/files_urlshort"
    cd "${SRC_DIR}/files_urlshort"
    tar -xf "${SCRIPT_DIR}/files_urlshort.tar"
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ files_urlshort.tar extracted ($(find ${SRC_DIR}/files_urlshort -type f | wc -l) files)${NC}"
else
    echo -e "${RED}✗ files_urlshort.tar not found${NC}"
fi

# Extract templates_urlshort.tar
echo -e "${YELLOW}[4/5] Extracting templates_urlshort.tar...${NC}"
if [ -f "${SCRIPT_DIR}/templates_urlshort.tar" ]; then
    mkdir -p "${SRC_DIR}/templates_urlshort"
    cd "${SRC_DIR}/templates_urlshort"
    tar -xf "${SCRIPT_DIR}/templates_urlshort.tar"
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ templates_urlshort.tar extracted ($(find ${SRC_DIR}/templates_urlshort -type f | wc -l) files)${NC}"
else
    echo -e "${RED}✗ templates_urlshort.tar not found${NC}"
fi

# Extract acptemplates_urlshort.tar
echo -e "${YELLOW}[5/5] Extracting acptemplates_urlshort.tar...${NC}"
if [ -f "${SCRIPT_DIR}/acptemplates_urlshort.tar" ]; then
    mkdir -p "${SRC_DIR}/acptemplates_urlshort"
    cd "${SRC_DIR}/acptemplates_urlshort"
    tar -xf "${SCRIPT_DIR}/acptemplates_urlshort.tar"
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ acptemplates_urlshort.tar extracted ($(find ${SRC_DIR}/acptemplates_urlshort -type f | wc -l) files)${NC}"
else
    echo -e "${RED}✗ acptemplates_urlshort.tar not found${NC}"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Unpacking completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Source directory: ${SRC_DIR}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Show directory structure
echo -e "${YELLOW}Directory structure:${NC}"
tree -L 3 "${SRC_DIR}" 2>/dev/null || find "${SRC_DIR}" -maxdepth 3 -type d | sed 's|[^/]*/| |g'
